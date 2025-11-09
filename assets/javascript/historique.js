// Gestion de la navigation par onglets
let col1 = document.querySelector('.col1');
let col2 = document.querySelector('.col2');
let button1 = document.querySelector('.button1change');
let button2 = document.querySelector('.button2change');
let container = document.querySelector('.container');

// Affichage de la première colonne (onglet actif)
button1.addEventListener('click', () => {
  col1.style.display = 'block';
  col2.style.display = 'none';
  button1.classList.add('active');
  button2.classList.remove('active');
});

// Affichage de la deuxième colonne (onglet actif)
button2.addEventListener('click', () => {
  col2.style.display = 'block';
  col1.style.display = 'none';
  button2.classList.add('active');
  button1.classList.remove('active');
});