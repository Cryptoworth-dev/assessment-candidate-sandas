import { Component, inject, signal } from '@angular/core';
import { NonNullableFormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

import { AuthService } from '../../services/auth';

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './register.html',
  styleUrl: './register.css',
})
export class Register {
  private fb = inject(NonNullableFormBuilder);
  private authService = inject(AuthService);
  private router = inject(Router);

  isSubmitting = signal(false);
  serverErrors = signal<Record<string, string[]>>({});

  form = this.fb.group({
    name: this.fb.control('', Validators.required),
    email: this.fb.control('', [Validators.required, Validators.email]),
    password: this.fb.control('', [Validators.required, Validators.minLength(8)]),
  });

  async submit(): Promise<void> {
    this.serverErrors.set({});

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const { name, email, password } = this.form.getRawValue();
    this.isSubmitting.set(true);

    try {
      await this.authService.register(name, email, password);
      this.router.navigateByUrl('/expenses');
    } catch (error: any) {
      this.isSubmitting.set(false);

      if (error?.status === 422) {
        this.serverErrors.set(error.error?.errors ?? {});
        return;
      }

      this.serverErrors.set({ email: ['Something went wrong. Please try again.'] });
      return;
    }

    this.isSubmitting.set(false);
  }

  serverError(field: string): string | null {
    return this.serverErrors()[field]?.[0] ?? null;
  }
}
