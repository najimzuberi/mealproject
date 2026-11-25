import { Injectable, computed, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, tap, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, AuthUser, LoginResult, OrganizationSummary } from '../models/api.model';

const TOKEN_KEY = 'mm_token';
const ORG_KEY = 'mm_organization_id';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly router = inject(Router);

  private readonly userSignal = signal<AuthUser | null>(null);
  private readonly orgIdSignal = signal<number | null>(this.readOrgId());

  readonly user = this.userSignal.asReadonly();
  readonly organizationId = this.orgIdSignal.asReadonly();
  readonly isAuthenticated = computed(() => !!this.getToken());
  readonly permissions = computed(() => this.userSignal()?.permissions ?? []);
  readonly roles = computed(() => this.userSignal()?.roles ?? []);
  readonly activeOrganization = computed(() => {
    const user = this.userSignal();
    const orgId = this.orgIdSignal();
    return user?.organizations.find((o) => o.id === orgId) ?? user?.organizations[0] ?? null;
  });
  readonly displayRole = computed(() => {
    const roles = this.roles();
    if (!roles.length) {
      return 'Member';
    }
    return roles[0].replace(/_/g, ' ');
  });

  getToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  }

  login(email: string, password: string): Observable<LoginResult> {
    return this.http
      .post<ApiResponse<LoginResult>>(`${environment.apiBaseUrl}/auth/login`, {
        email,
        password,
        device_name: 'mealmaster-web',
      })
      .pipe(
        map((res) => res.data),
        tap((data) => {
          localStorage.setItem(TOKEN_KEY, data.token);
          if (data.default_organization_id) {
            this.setOrganizationId(data.default_organization_id);
          }
        }),
      );
  }

  loadSession(): Observable<AuthUser> {
    return this.http.get<ApiResponse<AuthUser>>(`${environment.apiBaseUrl}/auth/me`).pipe(
      map((res) => res.data),
      tap((user) => {
        this.userSignal.set(user);
        if (!this.orgIdSignal() && user.active_organization_id) {
          this.setOrganizationId(user.active_organization_id);
        }
      }),
    );
  }

  switchOrganization(organization: OrganizationSummary): Observable<AuthUser> {
    this.setOrganizationId(organization.id);
    return this.loadSession();
  }

  setOrganizationId(id: number): void {
    localStorage.setItem(ORG_KEY, String(id));
    this.orgIdSignal.set(id);
  }

  hasPermission(required?: string | string[]): boolean {
    if (!required) {
      return true;
    }
    const perms = this.permissions();
    const needed = Array.isArray(required) ? required : [required];
    return needed.some((p) => perms.includes(p));
  }

  logout(): void {
    const token = this.getToken();
    if (token) {
      this.http.post(`${environment.apiBaseUrl}/auth/logout`, {}).subscribe({ error: () => undefined });
    }
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(ORG_KEY);
    this.userSignal.set(null);
    this.orgIdSignal.set(null);
    void this.router.navigateByUrl('/login');
  }

  private readOrgId(): number | null {
    const raw = localStorage.getItem(ORG_KEY);
    return raw ? Number(raw) : null;
  }
}
