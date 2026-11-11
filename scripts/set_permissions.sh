#!/usr/bin/env bash
# ==============================================================================
# Скрипт установки эталонных прав доступа для платформы Jino JH04 (CloudLinux LVE)
# Проект: uffnv.ru (STREET FORUM & SHOP)
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

cd "${PROJECT_ROOT}" || exit 1

echo ">>> Настройка прав доступа для Jino JH04 (CloudLinux LVE) в: ${PROJECT_ROOT}"

# 1. Все директории сайта — строго 755
echo "1/5 Установка 755 на директории..."
find . -type d -exec chmod 755 {} +

# 2. Все обычные файлы — строго 644
echo "2/5 Установка 644 на файлы..."
find . -type f -exec chmod 644 {} +

# 3. Изоляция конфигурации и секретов (152-ФЗ и защита от соседних LVE контейнеров)
echo "3/5 Установка 600 на локальные конфиги секретов..."
for secret_file in "config/db.local.php" "config/secrets.local.php"; do
    if [ -f "${secret_file}" ]; then
        chmod 600 "${secret_file}"
    fi
done

# 4. Каталоги пользовательских/админских загрузок и изолированных сессий
echo "4/5 Настройка uploads и storage/sessions..."
mkdir -p uploads/avatars assets/uploads storage/sessions
chmod -R 775 uploads uploads/avatars assets/uploads
chmod 700 storage storage/sessions 2>/dev/null || chmod 755 storage && chmod 700 storage/sessions
# Защитные .htaccess должны остаться 644
for ht in "uploads/avatars/.htaccess" "assets/uploads/.htaccess" "storage/sessions/.htaccess"; do
    if [ -f "${ht}" ]; then
        chmod 644 "${ht}"
    fi
done

# 5. Исполняемые скрипты консоли
echo "5/5 Права на скрипты..."
if [ -f "scripts/set_permissions.sh" ]; then
    chmod 755 scripts/set_permissions.sh
fi

echo ">>> [ГОТОВО] Права доступа успешно согласованы с архитектурой CloudLinux LVE."
