import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-auth-layout',
  standalone: true,
  imports: [RouterOutlet],
  template: `
    <div class="auth-shell">
      <div class="auth-shell__panel">
        <div class="auth-shell__brand">
          <div class="auth-shell__mark">MM</div>
          <div>
            <div class="auth-shell__title">MEAL MASTER</div>
            <div class="auth-shell__subtitle">Monitoring, Evaluation, Accountability & Learning</div>
          </div>
        </div>
        <router-outlet />
      </div>
    </div>
  `,
  styles: `
    .auth-shell {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 1.5rem;
      background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--mm-secondary) 18%, transparent), transparent 40%),
        linear-gradient(160deg, #0f2238 0%, var(--mm-primary) 45%, #1b3b57 100%);
    }
    .auth-shell__panel {
      width: min(28rem, 100%);
      background: var(--mm-surface);
      border-radius: 1rem;
      padding: 1.75rem;
      box-shadow: var(--mm-shadow-md);
    }
    .auth-shell__brand {
      display: flex;
      gap: 0.85rem;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    .auth-shell__mark {
      width: 2.75rem;
      height: 2.75rem;
      border-radius: 0.75rem;
      display: grid;
      place-items: center;
      background: var(--mm-accent);
      color: var(--mm-primary);
      font-weight: 800;
    }
    .auth-shell__title {
      font-weight: 700;
      color: var(--mm-heading);
    }
    .auth-shell__subtitle {
      color: var(--mm-body);
      font-size: 0.8rem;
    }
  `,
})
export class AuthLayoutComponent {}
