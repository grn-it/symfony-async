import http from 'k6/http';

export default function () {
  http.get('http://nginx/admin/statistics/sales');
}
