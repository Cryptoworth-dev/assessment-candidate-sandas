import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, input, output } from '@angular/core';

import { Expense } from '../../models/expense';

@Component({
  selector: 'app-expense-list',
  imports: [CurrencyPipe, DatePipe],
  templateUrl: './expense-list.html',
  styleUrl: './expense-list.css',
})
export class ExpenseList {
  expenses = input.required<Expense[]>();
  isLoading = input(false);
  loadError = input<string | null>(null);
  editingId = input<number | null>(null);

  edit = output<Expense>();
  remove = output<Expense>();
}