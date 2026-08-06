import { Routes } from '@angular/router';
import { authGuard } from './guards/auth-guard';

export const routes: Routes = [
  { path: 'login', loadComponent: () => import('./pages/login/login').then((m) => m.Login) },
  { path: 'register', loadComponent: () => import('./pages/register/register').then((m) => m.Register) },
  {
    path: 'expenses',
    canActivate: [authGuard],
    loadComponent: () => import('./pages/expenses-page/expenses-page').then((m) => m.ExpensesPage),
  },
  { path: '', pathMatch: 'full', redirectTo: 'expenses' },
  { path: '**', redirectTo: 'expenses' },
];
