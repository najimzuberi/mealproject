import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const token = auth.getToken();
  const orgId = auth.organizationId();

  let headers = req.headers;
  if (token) {
    headers = headers.set('Authorization', `Bearer ${token}`);
  }
  if (orgId && !req.url.includes('/auth/login')) {
    headers = headers.set('X-Organization-Id', String(orgId));
  }
  headers = headers.set('Accept', 'application/json');

  return next(req.clone({ headers }));
};
