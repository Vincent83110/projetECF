// Gestion de l'affichage/masquage du mot de passe

let hide = document.getElementById('showPassword');
let passWord = document.getElementById('mot-de-passe');

// Basculer entre l'affichage texte et mot de passe
hide.addEventListener('click',() => {
  if(passWord.type === 'password'){
    passWord.type = 'text';
  } else {
    passWord.type = 'password';
  };
})