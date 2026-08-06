<h1>Create account</h1>
<form class="stack-form" method="post" action="<?= url('/register') ?>">
    <?= csrf_field() ?>
    <label for="name">Name</label>
    <input id="name" name="name" type="text" value="<?= e(old('name')) ?>" maxlength="120" required>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" required>

    <label for="password">Password</label>
    <input id="password" name="password" type="password" minlength="10" required>

    <button class="button" type="submit">Create account</button>
</form>
<p class="muted">Already registered? <a href="<?= url('/login') ?>">Sign in</a>.</p>

