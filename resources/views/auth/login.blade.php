<x-guest-layout>
    <div class="mm-auth__intro">
        <h1 class="mm-auth__title">Sign in</h1>
        <p class="mm-auth__subtitle">Use your organization credentials to continue.</p>
    </div>

    <x-auth-session-status class="mb-4 text-sm text-teal-800" :status="session('status')" />

    <x-mm.form method="POST" :action="route('login')" class="mm-auth__form">
        <x-mm.form-field label="Email" name="email" required>
            <x-mm.text-input
                id="email"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="you@organization.org"
            />
        </x-mm.form-field>

        <x-mm.form-field label="Password" name="password" required>
            <x-mm.text-input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
            />
        </x-mm.form-field>

        <div class="mm-auth__row">
            <label for="remember_me" class="mm-auth__remember">
                <input id="remember_me" type="checkbox" name="remember" class="mm-auth__checkbox">
                <span>Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="mm-auth__link" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <x-slot:actions>
            <button type="submit" class="mm-btn mm-btn--primary mm-btn--block">
                Sign in
            </button>
        </x-slot:actions>
    </x-mm.form>

    @if (Route::has('register'))
        <p class="mm-auth__footer">
            Need an account?
            <a href="{{ route('register') }}" class="mm-auth__link">Register</a>
        </p>
    @endif
</x-guest-layout>
