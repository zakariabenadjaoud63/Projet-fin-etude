document.addEventListener("DOMContentLoaded", () => {
    const params = new URLSearchParams(window.location.search);
    const erreur = params.get('erreur');
    const messages = {
        champs_vides: "Veuillez remplir tous les champs obligatoires.",
        email_invalide: "L'adresse email n'est pas valide.",
        email_existe: "Cet email est deja utilise. Connectez-vous ou utilisez un autre email."
    };

    const zoneMessage = document.getElementById("erreur-inscription");
    if (zoneMessage && erreur && messages[erreur]) {
        zoneMessage.textContent = messages[erreur];
    }

    const formulaire = document.getElementById("inscription-form");
    const inputPassword = document.getElementById("client-password");
    const inputPasswordConfirm = document.getElementById("client-password-confirm");
    const inputTel = document.getElementById("client-tel");
    const bouton = document.querySelector('button[type="submit"]');

    if (formulaire) {
        formulaire.addEventListener("submit", (e) => {
            const password = inputPassword?.value ?? '';
            const passwordConfirm = inputPasswordConfirm?.value ?? '';
            const tel = inputTel?.value.trim() ?? '';

            if (password !== passwordConfirm) {
                e.preventDefault();
                if (zoneMessage) {
                    zoneMessage.textContent = "Les deux mots de passe ne correspondent pas.";
                }
                inputPasswordConfirm?.focus();
                return;
            }

            const regexTel = /^(05|06|07)[0-9]{8}$/;
            if (tel && !regexTel.test(tel)) {
                e.preventDefault();
                if (zoneMessage) {
                    zoneMessage.textContent = "Numero invalide. Format attendu : 0550000000.";
                }
                inputTel?.focus();
                return;
            }

            if (bouton) {
                bouton.disabled = true;
                bouton.textContent = "Creation en cours...";
            }
        });
    }
});
