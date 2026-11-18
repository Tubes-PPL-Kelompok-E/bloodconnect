"""PBI-04 Lokasi penyimpanan kantong (kapasitas dan rentang suhu)."""

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


def isi_lokasi(driver, nama, jenis, kapasitas, suhu_min, suhu_maks):
    buka(driver, "/admin/lokasi-penyimpanan")
    isi(driver, "nama", nama)
    pilih(driver, "jenis", jenis)
    isi(driver, "kapasitas", kapasitas)
    isi(driver, "suhu_min", suhu_min)
    isi(driver, "suhu_maks", suhu_maks)
    klik(driver, By.XPATH, "(//button[normalize-space()='Simpan'])[1]")


@pytest.mark.parametrize(
    "nama,jenis,kapasitas,suhu_min,suhu_maks,expected_type,expected_value",
    [
        # TC-01  EP kelas valid: lokasi cold room kapasitas 100, suhu 2-6
        ("AUTO", "cold_room", 100, 2, 6, "sukses", ""),
        # TC-02  BVA kapasitas 0: di bawah batas bawah 1
        ("AUTO", "cold_room", 0, "", "", "html5_invalid", "kapasitas"),
        # TC-03  BVA kapasitas 1: batas bawah
        ("AUTO", "cold_room", 1, "", "", "sukses", ""),
        # TC-04  EP kelas invalid: kapasitas kosong
        ("AUTO", "cold_room", "", "", "", "html5_required", "kapasitas"),
        # TC-05  EP kelas invalid: nama lokasi duplikat
        ("DUPLIKAT", "cold_room", 5, "", "", "error", ""),
        # TC-06  EP kelas invalid: suhu maks (2) lebih kecil dari suhu min (8)
        ("AUTO", "cold_room", 5, 8, 2, "error", ""),
    ],
    ids=["TC-01", "TC-02", "TC-03", "TC-04", "TC-05", "TC-06"],
)
def test_lokasi(driver, nama, jenis, kapasitas, suhu_min, suhu_maks, expected_type, expected_value):
    login(driver, "admin_pmi")
    nama_unik = uniq("CR-")
    if nama == "AUTO":
        nama = nama_unik
    elif nama == "DUPLIKAT":
        nama = nama_unik
        isi_lokasi(driver, nama, "cold_room", 5, "", "")      # buat lebih dulu
        cek_hasil(driver, "sukses", "")
    isi_lokasi(driver, nama, jenis, kapasitas, suhu_min, suhu_maks)
    cek_hasil(driver, expected_type, expected_value)
    if expected_type == "sukses":
        assert nama in driver.page_source


def test_hapus_lokasi_kosong(driver):
    # EP kelas valid: lokasi tanpa kantong boleh dihapus
    login(driver, "admin_pmi")
    nama = uniq("CR-")
    isi_lokasi(driver, nama, "cold_room", 5, "", "")
    cek_hasil(driver, "sukses", "")
    klik(driver, By.XPATH,
         f"(//input[@name='nama' and @value='{nama}']/ancestor::form)/following::form[1]//button")
    setuju_dialog(driver)
    cek_hasil(driver, "sukses", "")
    assert f'value="{nama}"' not in driver.page_source
