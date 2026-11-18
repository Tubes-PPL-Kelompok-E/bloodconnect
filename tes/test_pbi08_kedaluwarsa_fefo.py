"""PBI-08 Stok urut FEFO dan pencatatan pemusnahan kantong kedaluwarsa.

Penandaan otomatis status 'kedaluwarsa' dijalankan penjadwal
(php artisan bloodconnect:tandai-kedaluwarsa) dan tidak bisa dipicu dari antarmuka, jadi tidak
diuji di sini. Yang diuji adalah efeknya di UI stok.
"""

import os
import re
import uuid
from datetime import date, timedelta
from urllib.parse import urlparse

import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait

BASE_URL = os.getenv("BASE_URL", "http://127.0.0.1:8000")
TIMEOUT = 10

# Kotak pesan pada layout aplikasi (flash sukses = hijau, kesalahan = merah)
KOTAK_ERROR = (By.CSS_SELECTOR, "div.bg-red-50.border-red-200")
KOTAK_SUKSES = (By.CSS_SELECTOR, "div.bg-green-50.border-green-200")

# Akun uji dari database/seeders/UserSeeder.php
AKUN = {
    "admin_pmi": "admin.pmi@bloodconnect.id",
    "petugas_rs": "petugas.rs@bloodconnect.id",
    "pendonor": "pendonor@bloodconnect.id",   # terhubung ke "Pendonor Contoh" (DemoSeeder)
}
PASSWORD_BENAR = "password123"


@pytest.fixture
def driver():
    options = webdriver.ChromeOptions()
    if os.getenv("HEADLESS") == "1":
        options.add_argument("--headless=new")
    d = webdriver.Chrome(options=options)
    d.maximize_window()
    yield d
    d.quit()


def buka(driver, url):
    driver.get(BASE_URL + url)


def path_sekarang(driver):
    return urlparse(driver.current_url).path


def uniq(awal=""):
    return f"{awal}{uuid.uuid4().hex[:8]}"


def hari(selisih=0):
    """Tanggal ISO hari ini ditambah selisih hari."""
    return (date.today() + timedelta(days=selisih)).isoformat()


def klik(driver, by, selector):
    el = WebDriverWait(driver, TIMEOUT).until(EC.presence_of_element_located((by, selector)))
    driver.execute_script("arguments[0].scrollIntoView({block:'center'});", el)
    WebDriverWait(driver, TIMEOUT).until(EC.element_to_be_clickable((by, selector)))
    el.click()


def isi(driver, name, nilai):
    """Isi field; nilai kosong ('' atau None) berarti field dikosongkan."""
    el = WebDriverWait(driver, TIMEOUT).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, f"[name='{name}']")))
    el.clear()
    if nilai is not None and str(nilai) != "":
        el.send_keys(str(nilai))


def isi_js(driver, name, nilai):
    """Untuk input date/time agar tidak bergantung pada locale browser."""
    el = driver.find_element(By.CSS_SELECTOR, f"[name='{name}']")
    driver.execute_script(
        "arguments[0].value = arguments[1];"
        "arguments[0].dispatchEvent(new Event('input', {bubbles:true}));"
        "arguments[0].dispatchEvent(new Event('change', {bubbles:true}));", el, str(nilai))


def pilih(driver, name, value):
    Select(driver.find_element(By.CSS_SELECTOR, f"select[name='{name}']")).select_by_value(value)


def radio(driver, name, value):
    """Radio golongan/rhesus/hasil uji disembunyikan (sr-only), jadi diklik lewat JS."""
    driver.execute_script(
        "document.querySelector(\"input[name='%s'][value='%s']\").click();" % (name, value))


def setuju_dialog(driver):
    WebDriverWait(driver, TIMEOUT).until(EC.alert_is_present()).accept()


def login(driver, role):
    """Masuk lewat halaman login dengan akun seeder sesuai peran."""
    buka(driver, "/login")
    klik(driver, By.CSS_SELECTOR, f"button[data-role='{role}']")
    isi(driver, "email", AKUN[role])
    isi(driver, "password", PASSWORD_BENAR)
    klik(driver, By.CSS_SELECTOR, "form button:not([type='button'])")
    WebDriverWait(driver, TIMEOUT).until(lambda d: path_sekarang(d) != "/login")


def cek_hasil(driver, expected_type, expected_value):
    """Pemeriksa hasil bersama, tiap jenis hasil sama seperti pada test_login."""
    if expected_type == "url":
        WebDriverWait(driver, TIMEOUT).until(EC.url_contains(expected_value))
        assert path_sekarang(driver) == expected_value

    elif expected_type == "keluar_form":
        # Berpindah dari halaman form = server menerima data
        WebDriverWait(driver, TIMEOUT).until(lambda d: path_sekarang(d) != expected_value)

    elif expected_type == "error":
        pesan = WebDriverWait(driver, TIMEOUT).until(
            EC.visibility_of_element_located(KOTAK_ERROR)
        ).text
        assert expected_value in pesan

    elif expected_type == "sukses":
        pesan = WebDriverWait(driver, TIMEOUT).until(
            EC.visibility_of_element_located(KOTAK_SUKSES)
        ).text
        assert expected_value in pesan

    elif expected_type == "html5_required":
        field = driver.find_element(By.CSS_SELECTOR, f"[name='{expected_value}']")
        assert driver.execute_script("return arguments[0].validity.valueMissing;", field)

    elif expected_type == "html5_invalid":
        # Batas min/max/type ditolak browser sehingga form tidak terkirim
        field = driver.find_element(By.CSS_SELECTOR, f"[name='{expected_value}']")
        assert not driver.execute_script("return arguments[0].validity.valid;", field)

    elif expected_type == "halaman_memuat":
        assert expected_value in driver.page_source

    elif expected_type == "403":
        assert "403" in driver.page_source or "Forbidden" in driver.page_source

    else:
        raise AssertionError(f"expected_type tidak dikenal: {expected_type}")


def kode_batch():
    return "BATCH-" + uuid.uuid4().hex[:8].upper()


def isi_kantong(driver, kode, volume=350, donor=-2, komponen="prc", golongan="A", rhesus="pos"):
    buka(driver, "/admin/kantong-darah/create")
    isi(driver, "kode_batch", kode)
    isi_js(driver, "tanggal_donor", hari(donor))
    isi(driver, "volume_ml", volume)
    pilih(driver, "komponen", komponen)
    radio(driver, "golongan", golongan)
    radio(driver, "rhesus", rhesus)
    klik(driver, By.XPATH, "//button[contains(.,'Simpan Kantong')]")


def id_kantong(driver, kode):
    """ID kantong diambil dari tautan 'Ubah' di baris stok (hanya ada untuk kantong karantina)."""
    buka(driver, f"/admin/stok-darah?q={kode}")
    href = driver.find_element(
        By.XPATH, f"//tr[contains(.,'{kode}')]//a[contains(.,'Ubah')]").get_attribute("href")
    return re.search(r"/kantong-darah/(\d+)", href).group(1)


def buat_kantong(driver, kode=None, donor=-2):
    """Catat kantong baru (status awal karantina) dan kembalikan kode batch-nya."""
    kode = kode or kode_batch()
    isi_kantong(driver, kode, donor=donor)
    WebDriverWait(driver, TIMEOUT).until(EC.url_contains("/admin/stok-darah"))
    return kode


@pytest.mark.parametrize(
    "aksi,expected_type,expected_value",
    [
        # TC-01  Daftar stok urut FEFO: kantong kedaluwarsa lebih dekat tampil di atas
        ("fefo", "urutan", ""),
        # TC-02  Kantong belum kedaluwarsa tidak punya tombol 'Catat Pemusnahan'
        ("tanpa_tombol_musnah", "tombol_tidak_ada", ""),
        # TC-03  Pencarian batch yang ada
        ("cari_ada", "halaman_memuat", ""),
        # TC-04  Pencarian batch yang tidak ada
        ("cari_kosong", "halaman_memuat", "Tidak ada kantong yang cocok"),
    ],
    ids=["TC-01", "TC-02", "TC-03", "TC-04"],
)
def test_stok(driver, aksi, expected_type, expected_value):
    login(driver, "admin_pmi")
    awalan = uniq("T").upper()

    if aksi == "fefo":
        baru, lama = f"BATCH-{awalan}-B", f"BATCH-{awalan}-A"
        buat_kantong(driver, kode=baru, donor=-1)
        buat_kantong(driver, kode=lama, donor=-10)
        buka(driver, f"/admin/stok-darah?q={awalan}")
        html = driver.page_source
        assert html.index(lama) < html.index(baru)          # kedaluwarsa terdekat di atas

    elif aksi == "tanpa_tombol_musnah":
        kode = buat_kantong(driver)
        buka(driver, f"/admin/stok-darah?q={kode}")
        tombol = driver.find_elements(
            By.XPATH, f"//tr[contains(.,'{kode}')]//button[contains(.,'Catat Pemusnahan')]")
        assert tombol == []

    elif aksi == "cari_ada":
        kode = buat_kantong(driver)
        buka(driver, f"/admin/stok-darah?q={kode}")
        cek_hasil(driver, expected_type, kode)

    elif aksi == "cari_kosong":
        buka(driver, "/admin/stok-darah?q=TIDAK-ADA-XYZ")
        cek_hasil(driver, expected_type, expected_value)
