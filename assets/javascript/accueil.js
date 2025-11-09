// Gestion du carrousel principal et des fonctionnalités de la page d'accueil

let button1left = document.querySelector('.arrow-left1');
let button2left = document.querySelector('.arrow-left2');
let button3left = document.querySelector('.arrow-left3');
let button4left = document.querySelector('.arrow-left4');
let button1right = document.querySelector('.arrow-right1');
let button2right = document.querySelector('.arrow-right2');
let button3right = document.querySelector('.arrow-right3');
let button4right = document.querySelector('.arrow-right4');
let div1 = document.querySelector('.div-frame1');
let div2 = document.querySelector('.div-frame2');
let div3 = document.querySelector('.div-frame3');
let div4 = document.querySelector('.div-frame4');

// Classe pour gérer le fonctionnement du carrousel
class Caroussel {
    constructor(){
        // Initialisation des boutons de navigation
        this.left1 = button1left;
        this.left2 = button2left;
        this.left3 = button3left;
        this.left4 = button4left;
        this.right1 = button1right;
        this.right2 = button2right;
        this.right3 = button3right;
        this.right4 = button4right;
        // Initialisation des divs du carrousel
        this.divs = [div1, div2, div3, div4];
        this.index = 0;
        // Affiche la première div au chargement
        this.showDiv(this.divs[this.index]);
    }

    // Affiche une div du carrousel
    showDiv(div){
        div.style.visibility = 'visible';
        div.style.opacity = '1';
    };

    // Cache une div du carrousel
    hideDiv(div){
        div.style.visibility = 'hidden';
        div.style.opacity = '0';
    };
    
    // Navigation vers la droite (suivant)
    onTheLeft(){
        this.hideDiv(this.divs[this.index]);
        // Passe à l'index suivant avec bouclage
        this.index = (this.index + 1) % this.divs.length;
        this.showDiv(this.divs[this.index]);
    };

    // Navigation vers la gauche (précédent)
    onTheRight(){
        this.hideDiv(this.divs[this.index]);
        // Passe à l'index précédent avec bouclage
        this.index = (this.index - 1 + this.divs.length) % this.divs.length;
        this.showDiv(this.divs[this.index]);
    };

    // Attache les événements aux boutons
    attachEvents() {
        // Boutons gauche
        this.left1.addEventListener('click', () => { this.onTheLeft() });
        this.left2.addEventListener('click', () => { this.onTheLeft() });
        this.left3.addEventListener('click', () => { this.onTheLeft() });
        this.left4.addEventListener('click', () => { this.onTheLeft() });

        // Boutons droite
        this.right1.addEventListener('click', () => { this.onTheRight() });
        this.right2.addEventListener('click', () => { this.onTheRight() });
        this.right3.addEventListener('click', () => { this.onTheRight() });
        this.right4.addEventListener('click', () => { this.onTheRight() });
    }
}

// Initialisation du carrousel
const caroussel = new Caroussel();
caroussel.attachEvents();

// Gestion du système "Voir plus/Voir moins"
const buttons = document.querySelectorAll('.seeMore');
const containers = document.querySelectorAll('.text-block');
const divs = document.querySelectorAll('.divs-framess');

buttons.forEach((btn, index) => {
  btn.addEventListener('click', () => {
    const div = divs[index];
    const container = containers[index];

    container.classList.toggle('expanded');
    if (container.classList.contains('expanded')) {
      btn.textContent = 'Voir moins';
      container.style.height = container.scrollHeight + 'px';
      div.classList.toggle('activeSeeMore');
    } else {
      btn.textContent = 'Voir plus';
      container.classList.toggle('heightSeeMore');
      div.classList.remove('activeSeeMore');
    }
  });
});