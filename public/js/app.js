const confirmsJS = document.querySelectorAll('.confirm-js');

confirmsJS.forEach(confirmJS => {
    confirmJS.addEventListener('click', (e) => {
        const messageConfirmation = confirm('Merci de cliquer sur OK pour confirmer');
        
        if (!messageConfirmation) {  
            e.preventDefault();
        }
    })
});