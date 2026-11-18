"""PBI-11 Dashboard ketersediaan stok dan batas minimum."""

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


INPUT_BATAS = "batas[A][pos][prc]"
SIMPAN_BATAS = "//input[@name='batas[A][pos][prc]']/ancestor::form//button[not(@type='button')]"
RESET_BATAS = (
    "//form[not(contains(@action,'logout')) and contains(@onsubmit,'confirm')]//*[self::button or self::input[@type='submit']] | "
    "//*[self::button or self::a or self::input[@type='submit']]"
    "[not(ancestor::form[contains(@action,'logout')]) and ("
    "contains(@onclick,'confirm') or "
    "contains(translate(.,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'reset') or "
    "contains(translate(.,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'bawaan') or "
    "contains(translate(.,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'default') or "
    "contains(translate(.,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'kembalikan')"
    ")]"
)


@pytest.mark.parametrize(
    "role,url,expected_type,expected_value",
    [
        # TC-01  Admin PMI melihat dashboard: judul halaman
        ("admin_pmi", "/admin/dashboard", "halaman_memuat", "Dashboard Ketersediaan Stok Darah"),
        # TC-02  Admin PMI melihat dashboard: kartu total kantong ready
        ("admin_pmi", "/admin/dashboard", "halaman_memuat", "TOTAL KANTONG READY"),
        # TC-03  Petugas RS melihat katalog stok (baca saja)
        ("petugas_rs", "/petugas/dashboard", "halaman_memuat", "KATALOG STOK"),
        # TC-04  Petugas RS tidak boleh membuka pengaturan batas minimum
        ("petugas_rs", "/admin/batas-minimum", "403", ""),
        # TC-05  Pendonor tidak boleh membuka data dashboard
        ("pendonor", "/dashboard/data", "403", ""),
        # TC-06  Pendonor tidak boleh membuka dashboard admin
        ("pendonor", "/admin/dashboard", "403", ""),
    ],
    ids=["TC-01", "TC-02", "TC-03", "TC-04", "TC-05", "TC-06"],
)
def test_akses_halaman(driver, role, url, expected_type, expected_value):
    login(driver, role)
    buka(driver, url)
    cek_hasil(driver, expected_type, expected_value)


@pytest.mark.parametrize(
    "nilai,expected_type,expected_value",
    [
        # TC-01  BVA batas minimum -1: di bawah batas bawah 0
        (-1, "html5_invalid", "batas[A][pos][prc]"),
        # TC-02  BVA batas minimum 0: batas bawah
        (0, "sukses", ""),
        # TC-03  EP kelas valid: batas minimum 80
        (80, "sukses", ""),
    ],
    ids=["TC-01", "TC-02", "TC-03"],
)
def test_ubah_batas_minimum(driver, nilai, expected_type, expected_value):
    login(driver, "admin_pmi")
    buka(driver, "/admin/batas-minimum")
    isi(driver, INPUT_BATAS, nilai)
    klik(driver, By.XPATH, SIMPAN_BATAS)
    cek_hasil(driver, expected_type, expected_value)


def test_endpoint_polling_json(driver):
    login(driver, "admin_pmi")
    buka(driver, "/dashboard/data")
    isi_json = driver.find_element(By.TAG_NAME, "body").text
    for kunci in ("ringkasan", "stok", "kritis", "alert", "diperbarui"):
        assert kunci in isi_json


def test_reset_batas_ke_bawaan(driver):
    login(driver, "admin_pmi")
    buka(driver, "/admin/batas-minimum")
    isi(driver, INPUT_BATAS, 77)
    klik(driver, By.XPATH, SIMPAN_BATAS)
    cek_hasil(driver, "sukses", "")
    klik(driver, By.XPATH, RESET_BATAS)
    setuju_dialog(driver)
    cek_hasil(driver, "sukses", "")
    nilai = driver.find_element(By.CSS_SELECTOR, f"[name='{INPUT_BATAS}']").get_attribute("value")
    assert nilai == "30"            # bawaan config: PRC = 30