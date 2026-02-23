document.querySelectorAll(".nav-links li").forEach(item => {
    item.addEventListener("click", function() {
        document.querySelectorAll(".nav-links li")
            .forEach(i => i.classList.remove("active"));
        this.classList.add("active");
    });
});
