// Gestion du menu de notifications

let notif = document.getElementById('toggleNotifications');

// Basculer l'affichage du menu des notifications
notif.addEventListener('click', () => {
    const menu = document.getElementById('notificationMenu');
    menu.classList.toggle('visible');
})