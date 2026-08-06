import { Component, input } from '@angular/core';
import { CategoryTotal, Summary } from '../../models/expense';
import { CurrencyPipe, DatePipe, PercentPipe } from '@angular/common';

@Component({
  selector: 'app-expense-summary',
  imports: [CurrencyPipe, PercentPipe],
  templateUrl: './expense-summary.html',
  styleUrl: './expense-summary.css',
})
export class ExpenseSummary {
  summary = input.required<Summary>();

  share(row: CategoryTotal): number {
    const total = Number(this.summary().total);

    return total > 0 ? Number(row.total) / total : 0;
  }
}
