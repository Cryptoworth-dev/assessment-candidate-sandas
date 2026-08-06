import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../environments/environment';
import { User } from '../models/user';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private http = inject(HttpClient);
  private baseUrl = environment.apiUrl.replace('/api', '');

  user = signal<User | null>(null);
  isCheckingSession = signal(true);

  get isAuthenticated(): boolean {
    return this.user() !== null;
  }

  checkSession(): void {
    this.http
      .get<{ data: User }>(`${environment.apiUrl}/me`, { withCredentials: true })
      .subscribe({
        next: (res) => {
          this.user.set(res.data);
          this.isCheckingSession.set(false);
        },
        error: () => {
          this.user.set(null);
          this.isCheckingSession.set(false);
        },
      });
  }

  async login(email: string, password: string): Promise<User> {
    await firstValueFrom(
      this.http.get(`${this.baseUrl}/sanctum/csrf-cookie`, { withCredentials: true })
    );

    const response = await firstValueFrom(
      this.http.post<{ data: User }>(`${environment.apiUrl}/login`, { email, password }, {
        withCredentials: true,
      })
    );

    this.user.set(response.data);
    return response.data;
  }

  async register(name: string, email: string, password: string): Promise<User> {
    await firstValueFrom(
      this.http.get(`${this.baseUrl}/sanctum/csrf-cookie`, { withCredentials: true })
    );

    const response = await firstValueFrom(
      this.http.post<{ data: User }>(`${environment.apiUrl}/register`, { name, email, password }, {
        withCredentials: true,
      })
    );

    this.user.set(response.data);
    return response.data;
  }

  async logout(): Promise<void> {
    await firstValueFrom(
      this.http.post(`${environment.apiUrl}/logout`, {}, { withCredentials: true })
    );

    this.user.set(null);
  }
}
