import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

import { AuthService } from '../services/auth';

export const authGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (auth.isAuthenticated) {
    return true;
  }

  if (!auth.isCheckingSession()) {
    return router.parseUrl('/login');
  }

  return new Promise<boolean>((resolve) => {
    const checkInterval = setInterval(() => {
      if (!auth.isCheckingSession()) {
        clearInterval(checkInterval);
        resolve(auth.isAuthenticated);
      }
    }, 50);

    setTimeout(() => {
      clearInterval(checkInterval);
      resolve(false);
    }, 5000);
  }).then((isAuth) => (isAuth ? true : router.parseUrl('/login')));
};
