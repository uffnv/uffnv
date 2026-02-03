import time
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from webdriver_manager.chrome import ChromeDriverManager

# --- НАСТРОЙКИ ---
# Укажите адрес ГЛАВНОЙ страницы или страницы входа
# Судя по вашим файлам, вход лежит в pages/login.php
BASE_URL = "http://uffn/pages/login.php" 
CREATE_TOPIC_URL = "http://uffn/pages/create_topic.php"

def run_visual_emulator():
    print("🚀 Запуск браузера...")

    # 1. Настраиваем Chrome, чтобы он притворялся телефоном
    mobile_emulation = {
        "deviceMetrics": { "width": 360, "height": 740, "pixelRatio": 3.0 },
        "userAgent": "Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Mobile Safari/537.36"
    }
    
    chrome_options = Options()
    chrome_options.add_experimental_option("mobileEmulation", mobile_emulation)
    
    # Убираем лишние логи, чтобы не мешали
    chrome_options.add_argument("--log-level=3") 

    # Запускаем драйвер
    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)

    try:
        # --- ШАГ 1: ВХОД В СИСТЕМУ ---
        print(f"🌍 Открываем страницу: {BASE_URL}")
        driver.get(BASE_URL)
        time.sleep(2) # Пауза, чтобы вы успели увидеть страницу

        print("✍️  Вводим логин/пароль...")
        # Ищем поля по атрибуту name="" (как в вашем HTML)
        # Обычно это <input name="email"> и <input name="password">
        try:
            email_input = driver.find_element(By.NAME, "email")
            email_input.send_keys("test@example.com") # ВАШ ЮЗЕР

            pass_input = driver.find_element(By.NAME, "password")
            pass_input.send_keys("password123") # ВАШ ПАРОЛЬ
            
            # Ищем кнопку отправки (обычно type="submit" или тег button)
            # Пытаемся найти кнопку внутри формы
            submit_btn = driver.find_element(By.XPATH, "//button[@type='submit'] | //input[@type='submit']")
            submit_btn.click()
            
            print("✅ Кнопка нажата. Ждем перезагрузки...")
            time.sleep(3) # Ждем, пока PHP обработает вход и перенаправит
            
        except Exception as e:
            print(f"⚠️ Не нашли поля ввода на странице входа. Проверьте names в HTML. Ошибка: {e}")

        # --- ШАГ 2: ПЕРЕХОД К СОЗДАНИЮ ТЕМЫ ---
        print(f"\n🌍 Переходим к созданию темы: {CREATE_TOPIC_URL}")
        driver.get(CREATE_TOPIC_URL)
        time.sleep(2)

        print("✍️  Заполняем тему...")
        try:
            # Предполагаемые name="" полей на странице create_topic.php
            driver.find_element(By.NAME, "title").send_keys("Тема из Selenium")
            driver.find_element(By.NAME, "content").send_keys("Это сообщение напечатал робот.")
            
            # Если есть выпадающий список (select) для категории
            # driver.find_element(By.NAME, "category_id").send_keys("1")

            submit_topic = driver.find_element(By.XPATH, "//button[@type='submit'] | //input[@type='submit']")
            submit_topic.click()
            print("✅ Тема отправлена!")
            
        except Exception as e:
             print(f"⚠️ Ошибка на странице темы: {e}")

        print("\n👀 Смотрим результат 5 секунд...")
        time.sleep(5)

    except Exception as global_e:
        print(f"❌ Критическая ошибка: {global_e}")
    
    finally:
        print("🏁 Закрываем браузер.")
        driver.quit()

if __name__ == "__main__":
    run_visual_emulator()
