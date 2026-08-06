import { Component, inject, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { ExpenseSummary } from './components/expense-summary/expense-summary';
import { ExpenseForm } from './components/expense-form/expense-form';
import { ExpenseList } from './components/expense-list/expense-list';
import { ExpenseService } from './services/expense';
import { Expense } from './models/expense';

@Component({
  selector: 'app-root',
  imports: [ExpenseSummary, ExpenseForm, ExpenseList],
  templateUrl: './app.html',
  styleUrl: './app.css'
})
export class App {
  protected expenseService = inject(ExpenseService);

  protected editing = signal<Expense | null>(null);
  protected pendingDelete = signal<Expense | null>(null);
  protected isDeleting = signal(false);

  ngOnInit(): void {
    this.expenseService.refresh();
  }

  protected startEdit(expense: Expense): void {
    this.editing.set(expense);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  protected stopEditing(): void {
    this.editing.set(null);
  }

  protected askDelete(expense: Expense): void {
    this.pendingDelete.set(expense);
  }

  protected cancelDelete(): void {
    this.pendingDelete.set(null);
  }
  
  protected confirmDelete(): void {
    const expense = this.pendingDelete();
    if (!expense) return;

    this.isDeleting.set(true);
    this.expenseService.deleteExpense(expense.id).subscribe({
      next: () => {
        this.isDeleting.set(false);
        this.pendingDelete.set(null);
        this.expenseService.refresh();
      },
      error: (error) => {
        console.error('Error deleting expense:', error);
        this.isDeleting.set(false);
        this.pendingDelete.set(null);
        alert('Failed to delete the expense. Please try again later.');
      }
    });
  }
}
