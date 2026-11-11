// assets/js/main.js — Клиентская валидация форм, переключатель пароля и защита от double-submit (паритет с mih)

(function () {
    'use strict';

    function getErrorEl(input) {
        const group = input.closest('.consent-block, .mb-3, .mb-4, .mb-5, .col-12, .col-md-6') || input.parentElement;
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

    function updatePasswordMeter(input, val, badCharRemoved) {
        const group = input.closest('.mb-3, .mb-4') || input.parentElement;
        if (!group) return;
        const bar = group.querySelector('.password-strength-fill');
        const label = group.querySelector('.password-strength-label');
        if (!bar || !label) return;

        let score = 0;
        if (val.length >= 6) score++;
        if (val.length >= 10) score++;
        if (/[A-Za-z]/.test(val) && /\d/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val) && /[A-Z]/.test(val)) score++;

        const widths = ['0%', '25%', '50%', '75%', '100%'];
        const colors = ['#e2e8f0', '#ef4444', '#f59e0b', '#3b82f6', '#10b981'];
        const texts = [
            'От 6 символов: латинская буква + цифра, без пробелов и кириллицы',
            'Слабый пароль',
            'Средний пароль',
            'Хороший пароль',
            'Надежный пароль'
        ];

        bar.style.width = widths[score];
        bar.style.backgroundColor = badCharRemoved ? '#ef4444' : colors[score];
        label.textContent = badCharRemoved ? 'Кириллица и пробелы запрещены — используйте латиницу!' : texts[score];
    }

    function validateInput(input, showEmpty) {
        const rule = input.dataset.rule;
        if (!rule) return true;
        const form = input.closest('form');
        const isLogin = form && form.dataset.authMode === 'login';
        let val = input.value;

        if (rule === 'username') {
            const hadBad = /[^A-Za-zА-Яа-яЁё0-9_.-]/.test(val);
            const cleaned = val.replace(/[^A-Za-zА-Яа-яЁё0-9_.-]/g, '');
            if (cleaned !== val) {
                input.value = cleaned;
                val = cleaned;
            }
            if (hadBad && !showEmpty) {
                setState(input, false, 'Удалены пробелы или запрещённые символы (разрешены буквы, цифры, _ . -).', false);
                return false;
            }
            if (!val && !showEmpty) {
                clearState(input);
                return false;
            }
            const ok = /^[A-Za-zА-Яа-яЁё0-9_.-]{3,30}$/.test(val);
            setState(input, ok, 'Никнейм: 3–30 символов (буквы, цифры, _ . -, без пробелов).', isLogin);
            return ok;
        }

        if (rule === 'login-id') {
            const hadSpace = /\s/.test(val);
            const cleaned = val.replace(/\s+/g, '');
            if (cleaned !== val) {
                input.value = cleaned;
                val = cleaned;
            }
            if (hadSpace && !showEmpty) {
                setState(input, false, 'Пробелы в логине или Email не допускаются.', isLogin);
                return false;
            }
            if (!showEmpty) {
                clearState(input);
                return val.length >= 3;
            }
            if (!val) {
                setState(input, false, 'Введите Email или никнейм.', isLogin);
                return false;
            }
            if (val.includes('@')) {
                const okEmail = val.length <= 100 && /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(val);
                setState(input, okEmail, 'Некорректный формат Email (пример: name@example.ru).', isLogin);
                return okEmail;
            }
            const okLogin = /^[A-Za-zА-Яа-яЁё0-9_.-]{3,30}$/.test(val);
            setState(input, okLogin, 'Никнейм: 3–30 символов (буквы, цифры, _ . -).', isLogin);
            return okLogin;
        }

        if (rule === 'email') {
            const hadBad = /[^A-Za-z0-9@._%+-]/.test(val);
            const cleaned = val.replace(/[^A-Za-z0-9@._%+-]/g, '').toLowerCase();
            if (cleaned !== val) {
                input.value = cleaned;
                val = cleaned;
            }
            if (hadBad && !showEmpty) {
                setState(input, false, 'Email должен вводиться только латиницей и без пробелов.', isLogin);
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
            let digits = val.replace(/\D/g, '');
            if (digits.length > 0 && digits[0] === '8') {
                digits = '7' + digits.slice(1);
            } else if (digits.length > 0 && digits[0] !== '7') {
                digits = '7' + digits;
            }
            digits = digits.slice(0, 11);

            let formatted = '';
            if (digits.length > 0) {
                formatted = '+7';
                if (digits.length > 1) formatted += ' (' + digits.slice(1, 4);
                if (digits.length >= 4) formatted += ')';
                if (digits.length > 4) formatted += ' ' + digits.slice(4, 7);
                if (digits.length > 7) formatted += '-' + digits.slice(7, 9);
                if (digits.length > 9) formatted += '-' + digits.slice(9, 11);
            }
            if (val !== '' && formatted !== val) {
                input.value = formatted;
            }

            if (!digits && !showEmpty) {
                clearState(input);
                return false;
            }
            const allSame = digits.length === 11 && /^(\d)\1{9}$/.test(digits.slice(1));
            const ok = digits.length === 11 && digits[0] === '7' && !allSame;
            setState(input, ok, allSame ? 'Укажите реальный номер телефона.' : 'Введите 11 цифр номера в формате +7 (XXX) XXX-XX-XX.', isLogin);
            return ok;
        }

        if (rule === 'password-register' || rule === 'password-optional') {
            if (rule === 'password-optional' && val === '') {
                clearState(input);
                return true;
            }
            const hadBad = /[^\x21-\x7E]/.test(val);
            const cleaned = val.replace(/[^\x21-\x7E]/g, '');
            if (cleaned !== val) {
                input.value = cleaned;
                val = cleaned;
            }
            if (rule === 'password-register') {
                updatePasswordMeter(input, val, hadBad);
                const confirmInput = form ? form.querySelector('[data-rule="password-confirm"]') : null;
                if (confirmInput && confirmInput.value.length > 0) {
                    validateInput(confirmInput, true);
                }
            }
            if (!val && !showEmpty) {
                if (!hadBad) clearState(input);
                return false;
            }
            const ok = val.length >= 6 && val.length <= 64 && /[A-Za-z]/.test(val) && /\d/.test(val);
            setState(input, ok, hadBad
                ? 'Удалены русские буквы/пробелы. Пароль: от 6 символов (латиница + цифра).'
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
            const hadSpace = /\s/.test(val);
            const cleaned = val.replace(/\s+/g, '');
            if (cleaned !== val) {
                input.value = cleaned;
                val = cleaned;
            }
            if (hadSpace && !showEmpty) {
                setState(input, false, 'Пробелы в пароле не допускаются.', false);
                return false;
            }
            if (!showEmpty) {
                clearState(input);
                return val.length >= 1;
            }
            const ok = val.length >= 1 && val.length <= 128;
            setState(input, ok, 'Введите пароль.', true);
            return ok;
        }

        if (rule === 'required-text') {
            const minLen = parseInt(input.dataset.min || '2', 10);
            const maxLen = parseInt(input.dataset.max || '200', 10);
            const trimmed = val.trim();
            if (!trimmed && !showEmpty) {
                clearState(input);
                return false;
            }
            const ok = trimmed.length >= minLen && trimmed.length <= maxLen;
            setState(input, ok, 'Длина должна быть от ' + minLen + ' до ' + maxLen + ' символов.', isLogin);
            return ok;
        }

        if (rule === 'price') {
            const num = Number(val);
            const minP = parseInt(input.dataset.min || '1', 10);
            const maxP = parseInt(input.dataset.max || '9999999', 10);
            const isOptional = input.dataset.optional === '1';
            if (val === '' && (isOptional || !showEmpty)) {
                clearState(input);
                return isOptional;
            }
            let ok = Number.isFinite(num) && Number.isInteger(num) && num >= minP && num <= maxP;
            let msg = 'Укажите целую сумму от ' + minP + ' до ' + maxP + '.';
            if (ok && input.dataset.gtField && form) {
                const baseInp = form.querySelector('[name="' + input.dataset.gtField + '"]');
                if (baseInp && Number(baseInp.value) >= num) {
                    ok = false;
                    msg = 'Старая цена должна быть строго больше текущей цены.';
                }
            }
            setState(input, ok, msg, false);
            return ok;
        }

        return true;
    }

    function initPasswordToggles() {
        document.querySelectorAll('.toggle-password-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetId = this.dataset.target;
                const grp = this.closest('.input-group');
                const input = (targetId && document.getElementById(targetId)) || (grp ? grp.querySelector('input') : null);
                if (!input) return;

                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                } else {
                    input.type = 'password';
                    if (icon) {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                }
            });
        });
    }

    function attachCapsAndLayoutHint(form) {
        const inputs = form.querySelectorAll('input[type="password"], input[data-rule="email"], input[data-rule="login-id"]');
        inputs.forEach(function (inp) {
            const group = inp.closest('.mb-3, .mb-4, .mb-5') || inp.parentElement;
            if (!group) return;
            let hint = group.querySelector('.auth-caps-hint');
            if (!hint && (inp.type === 'password' || inp.dataset.rule === 'email')) {
                hint = document.createElement('div');
                hint.className = 'auth-caps-hint';
                group.appendChild(hint);
            }
            if (!hint) return;

            function checkEvent(e) {
                const caps = e.getModifierState && e.getModifierState('CapsLock');
                const isCyrKey = e.key && /^[А-Яа-яЁё]$/.test(e.key);
                const hasCyrVal = inp.dataset.rule !== 'login-id' && /[А-Яа-яЁё]/.test(inp.value);
                if (caps) {
                    hint.textContent = 'Внимание: включён CapsLock!';
                    hint.classList.add('show');
                } else if ((inp.type === 'password' || inp.dataset.rule === 'email') && (isCyrKey || hasCyrVal)) {
                    hint.textContent = 'Включена русская раскладка! Переключитесь на английскую (ENG).';
                    hint.classList.add('show');
                } else if (e.type === 'keyup') {
                    hint.textContent = '';
                    hint.classList.remove('show');
                }
            }

            inp.addEventListener('keydown', checkEvent);
            inp.addEventListener('keyup', checkEvent);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPasswordToggles();

        document.querySelectorAll('form[data-validate-form]').forEach(function (form) {
            attachCapsAndLayoutHint(form);
            const fields = Array.from(form.elements || []).filter(function (el) {
                return el && el.dataset && el.dataset.rule;
            });

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

            form.querySelectorAll('input[type="checkbox"][required]').forEach(function (cb) {
                cb.addEventListener('change', function () {
                    setState(cb, cb.checked, 'Необходимо отметить согласие на обработку данных (152-ФЗ).', false);
                });
            });

            form.addEventListener('submit', function (e) {
                if (e.submitter && e.submitter.dataset && e.submitter.dataset.skipValidate === '1') {
                    return;
                }
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
                        setState(cb, false, 'Необходимо отметить согласие на обработку данных (152-ФЗ).', false);
                    } else {
                        clearState(cb);
                    }
                });

                if (!allValid) {
                    e.preventDefault();
                    if (firstInvalid) {
                        firstInvalid.focus();
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }

                if (form.dataset.preventDoubleSubmit === '1') {
                    const submitBtn = form.querySelector('button[type="submit"]:not([name])');
                    if (submitBtn) {
                        if (submitBtn.dataset.submitted === '1') {
                            e.preventDefault();
                            return;
                        }
                        submitBtn.dataset.submitted = '1';
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> ОТПРАВКА...';
                    }
                }
            });
        });
    });
})();
