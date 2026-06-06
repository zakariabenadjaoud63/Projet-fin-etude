document.addEventListener("DOMContentLoaded", () => {
    const params = new URLSearchParams(window.location.search);
    const zoneMessage = document.getElementById("erreur-login");

    if (zoneMessage && params.get('inscription') === 'success') {
        zoneMessage.textContent = "Compte cree avec succes. Connectez-vous maintenant.";
    }

    if (zoneMessage && params.get('erreur') === 'identifiants') {
        zoneMessage.textContent = "Email ou mot de passe incorrect.";
    }

    const bouton = document.querySelector('button[type="submit"]');
    const formulaire = document.getElementById("login-form");

    if (formulaire && bouton) {
        formulaire.addEventListener("submit", () => {
            bouton.disabled = true;
            bouton.textContent = "Connexion en cours...";
        });
    }
});
