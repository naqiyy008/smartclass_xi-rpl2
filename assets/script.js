document.addEventListener("DOMContentLoaded", function() {

    const loader = document.getElementById("loading-screen");
    const logoutBtn = document.querySelector(".btn-logout");

    // =========================
    // LOGOUT LOADING
    // =========================
    if(logoutBtn){
        logoutBtn.addEventListener("click", function(){
            loader.style.display = "flex";
        });
    }

});
