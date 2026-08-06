import { HttpClient } from "@angular/common/http";
import { inject, Injectable, signal } from "@angular/core";
import { Observable } from "rxjs";

import { environment } from "../../environments/environment";
import { Expense, InputExpense, Summary } from "../models/expense";

@Injectable({providedIn: "root"})
export class ExpenseService {
    private http = inject(HttpClient);
    private apiUrl = `${environment.apiUrl}/expenses`;

    expenses = signal<Expense[]>([]);
    summary = signal<Summary>({total: '0.00', by_category: []});
    isLoading = signal(false);
    loadError = signal<string | null>(null);

    loadExpenses(): void {
        this.isLoading.set(true);
        this.loadError.set(null);

        this.http.get<{data: Expense[]}>(this.apiUrl).subscribe({
            next: (response) => {
                this.expenses.set(response.data);
                this.isLoading.set(false);
            },
            error: (error) => {
                console.error('Error loading expenses:', error);
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