<dialog class="bank-dialog" id="account-dialog" aria-labelledby="account-title">
    <h2 id="account-title">Личный кабинет</h2>
    <div class="dialog-fields"><input type="tel" autocomplete="off" placeholder="Номер телефона" aria-label="Номер телефона" data-account-phone><input type="password" autocomplete="off" placeholder="Пароль" aria-label="Пароль" data-account-password><p class="field-error" role="alert" data-account-error hidden>Неверно указан пароль</p></div>
    <div class="dialog-actions"><button type="button" class="button primary" data-account-login>ОК</button><button type="button" class="button" data-register-open>Зарегистрироваться</button><button type="button" class="button" data-dialog-close>Отмена</button></div>
</dialog>
<dialog class="bank-dialog compact-dialog" id="register-dialog" aria-labelledby="register-title">
    <h2 id="register-title">Регистрация</h2><input type="text" inputmode="numeric" autocomplete="off" placeholder="Введите номер карты" aria-label="Введите номер карты" data-card-number>
    <div class="dialog-actions"><button type="button" class="button primary" data-register-check>ОК</button><button type="button" class="button" data-dialog-close>Отмена</button></div>
</dialog>
<dialog class="bank-dialog card-error-dialog" id="card-error-dialog" aria-labelledby="card-error-title">
    <h2 id="card-error-title">Регистрация</h2><p class="card-error-message">Такая карта в банке не зарегистрирована!</p><div class="dialog-actions"><button type="button" class="button primary" data-card-back>ОК</button><button type="button" class="button" data-dialog-close>Отмена</button></div>
</dialog>
<dialog class="bank-dialog compact-dialog" id="email-dialog" aria-labelledby="email-title">
    <h2 id="email-title">Отправить расчёт на Email</h2><form data-email-form><input type="email" name="email" required maxlength="254" autocomplete="email" placeholder="Введите Email" aria-label="Введите Email"><p class="field-error" role="alert" data-email-error hidden></p><div class="dialog-actions"><button type="submit" class="button primary">Отправить</button><button type="button" class="button" data-dialog-close>Отмена</button></div></form>
</dialog>
<div class="notification" role="status" data-email-notice hidden><span>Расчёт отправлен. Ожидайте письмо в течение 5 минут</span><button type="button" aria-label="Закрыть уведомление" data-notice-close>×</button></div>
