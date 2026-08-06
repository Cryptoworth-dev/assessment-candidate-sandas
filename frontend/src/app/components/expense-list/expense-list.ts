import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, computed, inject, input, output } from '@angular/core';

import { CATEGORIES, Expense, ExpenseFilters, PaginationMeta } from '../../models/expense';
import { NonNullableFormBuilder, ReactiveFormsModule } from '@angular/forms';
import { debounceTime } from 'rxjs/internal/operators/debounceTime';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ExpenseService } from '../../services/expense';

@Component({
  selector: 'app-expense-list',
  imports: [CurrencyPipe, DatePipe, ReactiveFormsModule],
  templateUrl: './expense-list.html',
  styleUrl: './expense-list.css',
})
export class ExpenseList {
  expenses = input.required<Expense[]>();
  isLoading = input(false);
  loadError = input<string | null>(null);
  editingId = input<number | null>(null);

  meta = input<PaginationMeta | null>(null);

  edit = output<Expense>();
  remove = output<Expense>();

  filtersChanged = output<ExpenseFilters>();
  pageChanged = output<number>();

  categories = CATEGORIES;

  private fb = inject(NonNullableFormBuilder);  
  private expenseService = inject(ExpenseService);

  filterForm = this.fb.group({
    category: this.fb.control(''),
    search: this.fb.control(''),
    from: this.fb.control(''),
    to: this.fb.control(''),
  });

  constructor() {
    this.filterForm.valueChanges
      .pipe(debounceTime(300), takeUntilDestroyed())
      .subscribe((value) => {
        this.filtersChanged.emit({
          category: value.category || undefined,
          search: value.search || undefined,
          from: value.from || undefined,
          to: value.to || undefined,
        });
      });
  }

  goToPage(page: number): void {
    this.pageChanged.emit(page);
  }

  activeFilterCount = computed(() => {
    const value = this.filterForm.value;
    return Object.values(value).filter((v) => !!v).length;
  });

  clearFilters(): void {
    this.filterForm.reset({ category: '', search: '', from: '', to: '' });
  }

  readonly rangeStart = computed(() => {
    const m = this.meta();
    if (!m || m.total === 0) return 0;
    return (m.current_page - 1) * m.per_page + 1;
  });

  readonly rangeEndValue = computed(() => {
    const m = this.meta();
    if (!m) return 0;
    return Math.min(m.current_page * m.per_page, m.total);
  });
  
  exportCsv(): void {
  const value = this.filterForm.value;

  this.expenseService.exportCsv({
    category: value.category || undefined,
    search: value.search || undefined,
    from: value.from || undefined,
    to: value.to || undefined,
  });
}
}