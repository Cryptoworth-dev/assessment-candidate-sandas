export interface Expense {
    id: number;
    description: string;
    amount: string;
    category: string;
    expense_date: string;
    created_at: string;
    updated_at: string;
}

export interface InputExpense {
    description: string;
    amount: number;
    category: string;
    expense_date: string;
}

export interface CategoryTotal {
    category: string;
    total: string;
}

export interface Summary {
    total: string;
    by_category: CategoryTotal[];
}

export const CATEGORIES = [
    'Food',
    'Transport',
    'Rent',
];
