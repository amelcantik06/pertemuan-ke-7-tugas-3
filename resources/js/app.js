import './bootstrap';

const userId = document.querySelector('meta[name="user-id"]')?.content;
const notification = document.querySelector('[data-realtime-notification]');
const notificationMessage = notification?.querySelector('[data-notification-message]');
const dismissButton = notification?.querySelector('[data-notification-dismiss]');
let dismissTimeout;

dismissButton?.addEventListener('click', () => {
    notification.hidden = true;
});

if (userId && notification && notificationMessage && window.Echo) {
    window.Echo.private(`user.${userId}`).listen('.OrderPlaced', (event) => {
        const total = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(event.total));
        const invoiceNumber = event.invoice_number || `#${event.order_id}`;

        notificationMessage.textContent = `Transaksi ${invoiceNumber} sebesar ${total} berhasil dibuat oleh ${event.user_name}.`;
        notification.hidden = false;
        window.clearTimeout(dismissTimeout);
        dismissTimeout = window.setTimeout(() => {
            notification.hidden = true;
        }, 6000);
    });
}
