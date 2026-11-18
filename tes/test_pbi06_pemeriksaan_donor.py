"""PBI-06 Pemeriksaan pra-donor dan penentuan kelayakan (EP + BVA tiap parameter)."""

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


def isi_pendonor(driver, nama, umur=30, no_hp="081234567890", kota="Jakarta"):
    buka(driver, "/admin/pendonor/buat")
    isi(driver, "nama", nama)
    isi_js(driver, "tanggal_lahir", hari(-int(365.25 * umur)))
    pilih(driver, "jenis_kelamin", "L")
    pilih(driver, "golongan", "O")
    pilih(driver, "rhesus", "pos")
    isi(driver, "no_hp", no_hp)
    isi(driver, "kota", kota)
    klik(driver, By.XPATH, "//form//button[normalize-space()='Simpan']")


def buat_pendonor(driver, nama=None, umur=30):
    """Buat pendonor baru dan tunggu halaman detailnya terbuka."""
    nama = nama or "Uji " + uniq()
    isi_pendonor(driver, nama, umur)
    WebDriverWait(driver, TIMEOUT).until(lambda d: path_sekarang(d) != "/admin/pendonor/buat")
    return nama


# Badge hasil pemeriksaan terbaru pada riwayat di halaman detail pendonor
BADGE = ("xpath", "(//span[contains(@class,'rounded-full')]"
                   "[normalize-space()='Layak' or normalize-space()='Tidak layak'])[1]")


@pytest.mark.parametrize(
    "hb,sistolik,diastolik,berat,suhu,nadi,expected_type,expected_value",
    [
        # TC-01  EP kelas valid: seluruh nilai normal
        (14.0, 120, 80, 60, 36.6, 72, "badge", "Layak"),
        # TC-02  BVA Hb 12.4: tepat di bawah batas bawah 12.5
        (12.4, 120, 80, 60, 36.6, 72, "badge", "Tidak layak"),
        # TC-03  BVA Hb 12.5: batas bawah
        (12.5, 120, 80, 60, 36.6, 72, "badge", "Layak"),
        # TC-04  BVA Hb 17.0: batas atas
        (17.0, 120, 80, 60, 36.6, 72, "badge", "Layak"),
        # TC-05  BVA Hb 17.1: tepat di atas batas atas
        (17.1, 120, 80, 60, 36.6, 72, "badge", "Tidak layak"),
        # TC-06  BVA sistolik 89: di bawah batas bawah 90
        (14.0, 89, 80, 60, 36.6, 72, "badge", "Tidak layak"),
        # TC-07  BVA sistolik 90: batas bawah
        (14.0, 90, 80, 60, 36.6, 72, "badge", "Layak"),
        # TC-08  BVA sistolik 160: batas atas
        (14.0, 160, 100, 60, 36.6, 72, "badge", "Layak"),
        # TC-09  BVA sistolik 161: di atas batas atas
        (14.0, 161, 100, 60, 36.6, 72, "badge", "Tidak layak"),
        # TC-10  BVA diastolik 59: di bawah batas bawah 60
        (14.0, 120, 59, 60, 36.6, 72, "badge", "Tidak layak"),
        # TC-11  BVA diastolik 100: batas atas
        (14.0, 120, 100, 60, 36.6, 72, "badge", "Layak"),
        # TC-12  BVA diastolik 101: di atas batas atas
        (14.0, 120, 101, 60, 36.6, 72, "badge", "Tidak layak"),
        # TC-13  BVA berat 44.9 kg: di bawah batas 45
        (14.0, 120, 80, 44.9, 36.6, 72, "badge", "Tidak layak"),
        # TC-14  BVA berat 45.0 kg: batas bawah
        (14.0, 120, 80, 45.0, 36.6, 72, "badge", "Layak"),
        # TC-15  BVA suhu 37.5: batas atas
        (14.0, 120, 80, 60, 37.5, 72, "badge", "Layak"),
        # TC-16  BVA suhu 37.6: di atas batas atas
        (14.0, 120, 80, 60, 37.6, 72, "badge", "Tidak layak"),
        # TC-17  BVA nadi 49: di bawah batas bawah 50
        (14.0, 120, 80, 60, 36.6, 49, "badge", "Tidak layak"),
        # TC-18  BVA nadi 50: batas bawah
        (14.0, 120, 80, 60, 36.6, 50, "badge", "Layak"),
        # TC-19  BVA nadi 100: batas atas
        (14.0, 120, 80, 60, 36.6, 100, "badge", "Layak"),
        # TC-20  BVA nadi 101: di atas batas atas
        (14.0, 120, 80, 60, 36.6, 101, "badge", "Tidak layak"),
        # TC-21  EP kelas invalid: Hb 99 (di luar 3-25, tidak masuk akal)
        (99, 120, 80, 60, 36.6, 72, "error", "Hemoglobin tidak masuk akal"),
        # TC-22  EP kelas invalid: diastolik 130 tidak lebih kecil dari sistolik 120
        (14.0, 120, 130, 60, 36.6, 72, "error", ""),
        # TC-23  EP kelas invalid: semua field kosong (ditolak validasi HTML5)
        (None, None, None, None, None, None, "html5_required", "hemoglobin"),
    ],
    ids=["TC-01", "TC-02", "TC-03", "TC-04", "TC-05", "TC-06", "TC-07", "TC-08", "TC-09", "TC-10", "TC-11", "TC-12", "TC-13", "TC-14", "TC-15", "TC-16", "TC-17", "TC-18", "TC-19", "TC-20", "TC-21", "TC-22", "TC-23"],
)
def test_pemeriksaan(driver, hb, sistolik, diastolik, berat, suhu, nadi,
                     expected_type, expected_value):
    login(driver, "admin_pmi")
    buat_pendonor(driver)
    for name, nilai in (("hemoglobin", hb), ("sistolik", sistolik), ("diastolik", diastolik),
                        ("berat_kg", berat), ("suhu_tubuh", suhu), ("nadi", nadi)):
        isi(driver, name, nilai)
    klik(driver, By.XPATH, "//button[contains(.,'Periksa')]")

    if expected_type == "badge":
        teks = WebDriverWait(driver, TIMEOUT).until(EC.visibility_of_element_located(BADGE)).text
        assert teks == expected_value
    else:
        cek_hasil(driver, expected_type, expected_value)
