// assets/js/main.js — Клиентская валидация форм и защита от double-submit (по стандарту mih)

(function () {
    'use strict';

    function getErrorEl(input) {
        const group = input.closest('.mb-3, .mb-4, .mb-5, .col-12, .col-md-6') || input.parentElement;
        if (!group) return null;
        let el = group.querySelector('.auth-field-error');
        if (!el) {
            el = document.createElement('div');
            el.className = 'auth-field-error';
            group.appendChild(el);
        }
        return el;
    }

    function clearState(input) {
        input.classList.remove('is-invalid', 'is-valid');
        const el = getErrorEl(input);
        if (el) {
            el.textContent = '';
            el.classList.remove('show');
        }
    }

    function setState(input, isValid, message, isLogin) {
        const el = getErrorEl(input);
        if (isValid) {
            if (isLogin) {
                clearState(input);
                return;
            }
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            if (el) {
                el.textContent = '';
                el.classList.remove('show');
            }
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            if (el) {
                el.textContent = message || 'Проверьте корректность поля.';
                el.classList.add('show');
            }
        }
    }

    function updatePasswordMeter(input, val, hadBad) {
        const group = input.closest('.mb-3, .mb-4') || input.parentElement;
        if (!group) return;
        let meter = group.querySelector('.auth-pw-meter');
        if (!meter) {
            meter = document.createElement('div');
            meter.className = 'auth-pw-meter';
            meter.innerHTML = '<div class="auth-pw-meter-bar"></div>';
            input.insertAdjacentElement('afterend', meter);
        }
        const bar = meter.querySelector('.auth-pw-meter-bar');
        let score = 0;
        if (val.length >= 6) score += 35;
        if (/[A-Za-z]/.test(val)) score += 25;
        if (/\d/.test(val)) score += 20;
        if (val.length >= 10 || /[^A-Za-z0-9]/.test(val)) score += 20;

        bar.style.width = val ? Math.min(100, score) + '%' : '0';
        bar.style.backgroundColor = hadBad || score < 50 ? '#dc3545' : (score < 80 ? '#f59e0b' : '#10b981');
    }

    function validateInput(input, showEmpty) {
        const rule = input.dataset.rule;
        if (!rule) return true;
        const form = input.closest('form');
        const isLogin = form && form.dataset.authMode === 'login';
        let val = input.value;

        if (rule === 'username') {
            const cleaned = val.replace(/\s+/g, '');
            if (cleaned !== val) {
                input.value = cleaned;
                val = cleaned;
            }
            if (!val && !showEmpty) {
                clearState(input);
                return false;
            }
            const ok = /^[A-Za-zА-Яа-яЁё0-9_.-]{3,30}$/.test(val);
            setState(input, ok, 'Никнейм: 3–30 символов (буквы, цифры, _ . -, без пробелов).', isLogin);
            return ok;
        }

        if (rule === 'email') {
            const hadBad = /[^A-Za-z0-9@._%+-]/.test(val);
            const cleaned = val.replace(/[^A-Za-z0-9@._%+-]/g, '').toLowerCase();
            if (cleaned !== val) {
                input.value = cleaned;
                val = cleaned;
            }
            if (hadBad && !showEmpty) {
                setState(input, false, 'Email вводится только латиницей без пробелов.', isLogin);
                return false;
            }
            if (!val && !showEmpty) {
                clearState(input);
                return false;
            }
            const ok = val.length <= 100 && /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(val);
            setState(input, ok, 'Введите корректный Email латиницей (пример: name@example.ru).', isLogin);
            return ok;
        }

        if (rule === 'phone') {
            const digits = val.replace(/\D/g, '');
            if (!digits && !showEmpty) {
                clearState(input);
                return false;
            }
            const body = digits.length === 11 ? digits.slice(1) : digits;
            const allSame = body.length === 10 && /^(\d)\1{9}$/.test(body);
            const ok = digits.length === 11 && (digits[0] === '7' || digits[0] === '8') && !allSame;
            setState(input, ok, allSame ? 'Укажите реальный номер телефона.' : 'Введите полный номер телефона: +7 (XXX) XXX-XX-XX.', isLogin);
            return ok;
        }

        if (rule === 'password-register') {
            const hadBad = /[^\x21-\x7E]/.test(val);
            const cleaned = val.replace(/[^\x21-\x7E]/g, '');
            if (cleaned !== val) {
                input.value = cleaned;
                val = cleaned;
            }
            updatePasswordMeter(input, val, hadBad);

            const confirmInput = form ? form.querySelector('[data-rule="password-confirm"]') : null;
            if (confirmInput && confirmInput.value.length > 0) {
                validateInput(confirmInput, true);
            }

            if (!val && !showEmpty) {
                clearState(input);
                return false;
            }
            const ok = val.length >= 6 && val.length <= 64 && /[A-Za-z]/.test(val) && /\d/.test(val);
            setState(input, ok, hadBad
                ? 'Удалили русские буквы/пробелы. Нужны латиница и цифры (от 6 символов).'
                : 'Минимум 6 символов: хотя бы 1 латинская буква и 1 цифра.', false);
            return ok;
        }

        if (rule === 'password-confirm') {
            const cleaned = val.replace(/[^\x21-\x7E]/g, '');
            if (cleaned !== val) {
                input.value = cleaned;
                val = cleaned;
            }
            if (!val && !showEmpty) {
                clearState(input);
                return false;
            }
            const pwdInput = form ? form.querySelector('[data-rule="password-register"]') : null;
            const ok = val.length >= 6 && pwdInput && val === pwdInput.value;
            setState(input, ok, 'Пароли не совпадают.', false);
            return ok;
        }

        if (rule === 'password-login') {
            if (!val && !showEmpty) {
                clearState(input);
                return false;
            }
            const ok = val.length > 0;
            setState(input, ok, 'Введите пароль.', true);
            return ok;
        }

        if (rule === 'required-text') {
            const minLen = parseInt(input.dataset.min || '2', 10);
            const trimmed = val.trim();
            if (!trimmed && !showEmpty) {
                clearState(input);
                return false;
            }
            const ok = trimmed.length >= minLen;
            setState(input, ok, 'Минимум ' + minLen + ' симв. Заполните поле.', isLogin);
            return ok;
        }

        return true;
    }

    function attachCapsAndLayoutHint(form) {
        const inputs = form.querySelectorAll('input[type="password"], input[data-rule="email"]');
        inputs.forEach(function (inp) {
            const group = inp.closest('.mb-3, .mb-4, .mb-5') || inp.parentElement;
            if (!group) return;
            let hint = group.querySelector('.auth-caps-hint');
            if (!hint) {
                hint = document.createElement('div');
                hint.className = 'auth-caps-hint';
                group.appendChild(hint);
            }
            ['keydown', 'keyup'].forEach(function (evName) {
                inp.addEventListener(evName, function (e) {
                    const caps = e.getModifierState && e.getModifierState('CapsLock');
                    const hasCyr = /[А-Яа-яЁё]/.test(inp.value);
                    if (caps) {
                        hint.textContent = 'Внимание: включён CapsLock';
                        hint.classList.add('show');
                    } else if (hasCyr) {
                        hint.textContent = 'Переключите раскладку на латиницу (ENG)';
                        hint.classList.add('show');
                    } else {
                        hint.textContent = '';
                        hint.classList.remove('show');
                    }
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-validate-form]').forEach(function (form) {
            attachCapsAndLayoutHint(form);
            const fields = form.querySelectorAll('[data-rule]');
            fields.forEach(function (field) {
                field.addEventListener('input', function () {
                    validateInput(field, false);
                });
                field.addEventListener('blur', function () {
                    if (field.value.trim() !== '') {
                        validateInput(field, true);
                    }
                });
            });

            form.addEventListener('submit', function (e) {
                let allValid = true;
                let firstInvalid = null;

                fields.forEach(function (field) {
                    if (!validateInput(field, true)) {
                        allValid = false;
                        if (!firstInvalid) firstInvalid = field;
                    }
                });

                form.querySelectorAll('input[type="checkbox"][required]').forEach(function (cb) {
                    if (!cb.checked) {
                        allValid = false;
                        if (!firstInvalid) firstInvalid = cb;
                        cb.classList.add('is-invalid');
                    } else {
                        cb.classList.remove('is-invalid');
                    }
                });

                if (!allValid) {
                    e.preventDefault();
                    if (firstInvalid) firstInvalid.focus();
                    return;
                }

                // Защита от двойного клика при отправке формы
                if (form.dataset.preventDoubleSubmit === '1') {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        if (submitBtn.dataset.submitted === '1') {
                            e.preventDefault();
                            return;
                        }
                        submitBtn.dataset.submitted = '1';
                        submitBtn.disabled = true;
                        submitBtn.dataset.origText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> ОТПРАВКА...';
                    }
                }
            });
        });
    });
})();
