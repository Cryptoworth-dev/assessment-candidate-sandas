import { HttpClient, HttpParams } from "@angular/common/http";
import { inject, Injectable, signal } from "@angular/core";
import { Observable } from "rxjs";

import { environment } from "../../environments/environment";
import { Expense, ExpenseFilters, InputExpense, PaginationMeta, Summary } from "../models/expense";

@Injectable({providedIn: "root"})
export class ExpenseService {
    private http = inject(HttpClient);
    private apiUrl = `${environment.apiUrl}/expenses`;

    expenses = signal<Expense[]>([]);
    summary = signal<Summary>({total: '0.00', by_category: []});
    isLoading = signal(false);
    loadError = signal<string | null>(null);

    meta = signal<PaginationMeta | null>(null);

    private currentFilters = signal<ExpenseFilters>({});

    loadExpenses(filters: ExpenseFilters = {}): void {
        this.currentFilters.set(filters);
        this.isLoading.set(true);
        this.loadError.set(null);

        let params = new HttpParams();
        if (filters.category) params = params.set('category', filters.category);
        if (filters.search) params = params.set('search', filters.search);
        if (filters.from) params = params.set('from', filters.from);
        if (filters.to) params = params.set('to', filters.to);
        if (filters.page) params = params.set('page', filters.page);

        this.http.get<{data: Expense[]; meta: PaginationMeta}>(this.apiUrl, { params }).subscribe({
            next: (response) => {
                this.expenses.set(response.data);
                this.meta.set(response.meta);
                this.isLoading.set(false);
            },
            error: (error) => {
                this.loadError.set('Failed to load expenses. Please try again later.');
                this.isLoading.set(false);
            }
        });
    }

    loadSummary(): void {
        this.http.get<{data: Summary}>(`${this.apiUrl}/summary`).subscribe({
            next: (response) => this.summary.set(response.data),
        });
    }

    refresh(): void {
        this.loadExpenses();
        this.loadSummary();
    }

    createExpense(input: InputExpense): Observable<{data: Expense}> {
        return this.http.post<{data: Expense}>(this.apiUrl, input);
    }

    updateExpense(id: number,input: InputExpense): Observable<{data: Expense}> {
        return this.http.put<{data: Expense}>(`${this.apiUrl}/${id}`, input);
    }

    deleteExpense(id: number): Observable<void> {
        return this.http.delete<void>(`${this.apiUrl}/${id}`);
    }
}