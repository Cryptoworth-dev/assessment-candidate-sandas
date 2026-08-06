import {
  AfterViewInit,
  Component,
  ElementRef,
  OnDestroy,
  effect,
  input,
  viewChild,
} from '@angular/core';
import { Chart, registerables } from 'chart.js';

import { Summary } from '../../models/expense';

Chart.register(...registerables);

@Component({
  selector: 'app-expense-chart',
  templateUrl: './expense-chart.html',
  styleUrl: './expense-chart.css',
})
export class ExpenseChart implements AfterViewInit, OnDestroy {
  summary = input.required<Summary>();

  private canvasRef = viewChild.required<ElementRef<HTMLCanvasElement>>('canvas');
  private chart: Chart | null = null;

  constructor() {
    effect(() => {
      const data = this.summary();

      if (this.chart) {
        this.chart.data.labels = data.by_category.map((row) => row.category);
        this.chart.data.datasets[0].data = data.by_category.map((row) => Number(row.total));
        this.chart.update();
      }
    });
  }

  ngAfterViewInit(): void {
    this.chart = new Chart(this.canvasRef().nativeElement, {
      type: 'doughnut',
      data: {
        labels: this.summary().by_category.map((row) => row.category),
        datasets: [
          {
            data: this.summary().by_category.map((row) => Number(row.total)),
            backgroundColor: [
              '#2f6f4f', '#5b8a72', '#8ba58f', '#b3261e', '#c97a74', '#71706a',
            ],
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' },
        },
      },
    });
  }

  ngOnDestroy(): void {
    this.chart?.destroy();
  }
}