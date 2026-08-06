import { Component, effect, inject, input, output, signal } from '@angular/core';
import { NonNullableFormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ExpenseService } from '../../services/expense';
import { CATEGORIES, Expense, InputExpense } from '../../models/expense';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  selector: 'app-expense-form',
  imports: [ReactiveFormsModule],
  templateUrl: './expense-form.html',
  styleUrl: './expense-form.css',
})
export class ExpenseForm {
  private fb = inject(NonNullableFormBuilder);
  private expenseService = inject(ExpenseService);

  editing = input<Expense | null>(null);
  saved = output<void>();
  cancelled = output<void>();

  categories = CATEGORIES;
  today = new Date().toISOString().slice(0, 10);

  isSaving = signal(false); 
  serverErrors = signal<Record<string, string | null>>({});

  form = this.fb.group({
    description: this.fb.control('', [Validators.required, Validators.maxLength(255)]),
    amount: this.fb.control<number | null>(null, [Validators.required, Validators.min(0.01)]),
    category: this.fb.control('', [Validators.required]),
    expense_date: this.fb.control('', [Validators.required]),
  });

  constructor() {
    effect(() => {
      const expense = this.editing();
      if (expense) {
        this.form.setValue({
          description: expense.description,
          amount: parseFloat(expense.amount),
          category: expense.category,
          expense_date: expense.expense_date,
        });
      } else {
        this.form.reset();
      }
      this.serverErrors.set({});
    });
  }

  submit(): void {
    this.serverErrors.set({});

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const value = this.form.getRawValue();
    const input: InputExpense = {
      description: value.description.trim(),
      amount: Number(value.amount),
      category: value.category,
      expense_date: value.expense_date,
    };

    const editing = this.editing();
    const request = editing
      ? this.expenseService.updateExpense(editing.id, input)
      : this.expenseService.createExpense(input);

    this.isSaving.set(true);

    request.subscribe({
      next: () => {
        this.isSaving.set(false);
        this.form.reset();
        this.expenseService.refresh();
        this.saved.emit();
      },
      error: (error: HttpErrorResponse) => {
        this.isSaving.set(false);

        if (error.status === 422) {
          this.serverErrors.set(error.error?.errors ?? {});
        }
      },
    });
  }

  cancel(): void {
    this.form.reset();
    this.serverErrors.set({});
    this.cancelled.emit();
  }

  serverError(field: string): string | null {
    return this.serverErrors()[field]?.[0] ?? null;
  }
}
