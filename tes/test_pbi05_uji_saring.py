"""PBI-05 Uji saring laboratorium: karantina -> ready (non-reaktif) atau ditahan (reaktif)."""

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


def unggah(driver, file):
    el = driver.find_element(By.ID, "berkas")
    driver.execute_script("arguments[0].style.display='block';", el)   # input file bersifat sr-only
    el.send_keys(str(file))


@pytest.mark.parametrize(
    "hasil,ba,tanggal,berkas,expected_type,expected_value",
    [
        # TC-01  EP kelas valid: hasil non-reaktif dengan berkas PDF
        ("non_reaktif", "BA-001/UTD/2026", 0, "pdf", "status_stok", "Ready"),
        # TC-02  EP kelas valid: hasil reaktif (status Ditahan)
        ("reaktif", "BA-002/UTD/2026", 0, "pdf", "status_stok", "Ditahan"),
        # TC-03  EP kelas invalid: berkas tidak diunggah
        ("non_reaktif", "BA-003/UTD/2026", 0, "none", "error", "Berkas berita acara wajib diunggah."),
        # TC-04  EP kelas invalid: berkas berformat .exe
        ("non_reaktif", "BA-004/UTD/2026", 0, "exe", "error", "Berkas harus berformat PDF, JPG, atau PNG."),
        # TC-05  EP kelas invalid: nomor berita acara kosong
        ("non_reaktif", "", 0, "pdf", "error", ""),
        # TC-06  BVA tanggal uji besok (batas atas = hari ini)
        ("non_reaktif", "BA-005/UTD/2026", 1, "pdf", "html5_invalid", "tanggal_uji"),
    ],
    ids=["TC-01", "TC-02", "TC-03", "TC-04", "TC-05", "TC-06"],
)
def test_uji_saring(driver, tmp_path, hasil, ba, tanggal, berkas, expected_type, expected_value):
    login(driver, "admin_pmi")
    kode = buat_kantong(driver)
    # Antrean hanya menampilkan 5 kantong terlama, jadi kantong baru dibuka lewat ?batch=ID
    buka(driver, f"/admin/uji-saring?batch={id_kantong(driver, kode)}")

    radio(driver, "hasil_uji", hasil)
    isi(driver, "nomor_berita_acara", ba)
    isi_js(driver, "tanggal_uji", hari(tanggal))
    if berkas == "pdf":
        f = tmp_path / "ba.pdf"
        f.write_bytes(b"%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF")
        unggah(driver, f)
    elif berkas == "exe":
        f = tmp_path / "virus.exe"
        f.write_bytes(b"MZ0000")
        unggah(driver, f)
    klik(driver, By.XPATH, "//button[contains(.,'Simpan Hasil')]")

    if expected_type == "status_stok":
        # Setelah diuji, status kantong di halaman stok berubah (Ready / Ditahan)
        cek_hasil(driver, "sukses", "")
        assert path_sekarang(driver) == "/admin/uji-saring"
        buka(driver, f"/admin/stok-darah?q={kode}")
        baris = driver.find_element(By.XPATH, f"//tr[contains(.,'{kode}')]").text
        assert expected_value in baris
    else:
        cek_hasil(driver, expected_type, expected_value)
