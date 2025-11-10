document.addEventListener("DOMContentLoaded", () => {
  // ===== Variables =====
  let sideBar = document.getElementById("mySidebar");
  let main1 = document.getElementById("main");
  let buttonAside = document.getElementById("buttonAside");
  let pop = document.getElementById("pop");
  let closebtn = document.getElementById("closebtn");

  let menu = document.getElementById("InsideNav");
  let Nav = document.getElementById("Nav");
  let FlecheDown = document.getElementById("ImageIcon");
  let header = document.querySelector("header");

  // ===== Sidebar =====
  buttonAside.addEventListener('click', () => {
    sideBar.style.width = "250px";
  });

  const closeSidebar = () => {
    sideBar.style.width = "0";
  };

  pop.addEventListener('click', closeSidebar);
  closebtn.addEventListener('click', closeSidebar);

  // ===== Menu déroulant =====
  Nav.addEventListener('click', () => {
    let Display = window.getComputedStyle(menu);

    if (Display.visibility === "hidden") {
      menu.style.visibility = "visible";
      menu.style.opacity = "1";
      menu.style.pointerEvents = "auto";
      header.style.zIndex = "3";
      FlecheDown.src = `${BASE_URL}/assets/images/flecheMenuBas.svg`;
    } else {
      menu.style.visibility = "hidden";
      menu.style.opacity = "0";
      menu.style.pointerEvents = "none";
      header.style.zIndex = "1";
      FlecheDown.src = `${BASE_URL}/assets/images/flecheMenu.svg`;
    }
  });

  // ===== État initial =====
  let Display = window.getComputedStyle(menu);

  if (Display.visibility === "hidden") {
    header.style.zIndex = "1";
    FlecheDown.src = `${BASE_URL}/assets/images/flecheMenu.svg`;
  } else {
    header.style.zIndex = "3";
    FlecheDown.src = `${BASE_URL}/assets/images/flecheMenuBas.svg`;
  }
});
