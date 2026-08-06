import { HttpInterceptorFn } from '@angular/common/http';

export const csrfInterceptor: HttpInterceptorFn = (req, next) => {
  const token = getCsrfToken();

  let updatedReq = req;

  if (token && !req.headers.has('X-XSRF-TOKEN')) {
    updatedReq = updatedReq.clone({
      setHeaders: {
        'X-XSRF-TOKEN': token,
      },
    });
  }

  if (!updatedReq.withCredentials) {
    updatedReq = updatedReq.clone({
      withCredentials: true,
    });
  }

  return next(updatedReq);
};

function getCsrfToken(): string | null {
  const name = 'XSRF-TOKEN';
  let cookieValue: string | null = null;
  if (document.cookie && document.cookie !== '') {
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
      cookie = cookie.trim();
      if (cookie.substring(0, name.length + 1) === (name + '=')) {
        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
        break;
      }
    }
  }
  return cookieValue;
}
