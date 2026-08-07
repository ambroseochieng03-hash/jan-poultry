/*
|--------------------------------------------------------------------------
| JAN Poultry Homepage
|--------------------------------------------------------------------------
*/

document.addEventListener(

    'DOMContentLoaded',

    function(){

        /*
        ------------------------------------
        Reveal Sections
        ------------------------------------
        */

        const reveals =

            document.querySelectorAll(
                '.reveal'
            );

        function revealSections(){

            reveals.forEach(function(section){

                const top =
                    section.getBoundingClientRect().top;

                if(top < window.innerHeight - 120){

                    section.classList.add(
                        'active'
                    );

                }

            });

        }

        revealSections();

        window.addEventListener(

            'scroll',

            revealSections

        );
});

/*
|--------------------------------------------------------------------------
| JAN Poultry
| Homepage
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    () => {

        /*
        -------------------------------------------------------
        Product Preview Modal
        -------------------------------------------------------
        */

        const modal =
            document.getElementById(
                'featuredProductModal'
            );

        if (modal) {

            const cards =
                document.querySelectorAll(
                    '.home-product-card'
                );

            const closeButton =
                document.getElementById(
                    'closePreviewModal'
                );

            const image =
                document.getElementById(
                    'previewImage'
                );

            const video =
                document.getElementById(
                    'previewVideo'
                );

            const videoSource =
                document.getElementById(
                    'previewVideoSource'
                );    

            const name =
                document.getElementById(
                    'previewName'
                );

            const category =
                document.getElementById(
                    'previewCategory'
                );

            const description =
                document.getElementById(
                    'previewDescription'
                );

            const breed =
                document.getElementById(
                    'previewBreed'
                );

            const type =
                document.getElementById(
                    'previewType'
                );

            const availability =
                document.getElementById(
                    'previewAvailability'
                );

            function openModal(card)
            {

                const file = card.dataset.image;

                const extension =
                    file.split('.').pop().toLowerCase();

                if (extension === 'mp4') {

                    image.style.display = 'none';

                    video.style.display = 'block';

                    videoSource.src = file;

                    video.load();

                }
                else {

                    video.pause();

                    video.style.display = 'none';

                    image.style.display = 'block';

                    image.src = file;

                    image.alt = card.dataset.name;

                }

                name.textContent =
                    card.dataset.name;

                category.textContent =
                    card.dataset.category;

                description.textContent =
                    card.dataset.description;

                breed.textContent =
                    card.dataset.breed;

                type.textContent =
                    card.dataset.type;

                availability.textContent =
                    card.dataset.available;

                if (
                    card.dataset.available ===
                    'Coming Soon'
                ) {

                    availability.style.background =
                        '#dc2626';

                } else {

                    availability.style.background =
                        '#22c55e';

                }

                modal.classList.add(
                    'show'
                );

                document.body.style.overflow =
                    'hidden';

            }

            function closeModal()
            {

                modal.classList.remove(
                    'show'
                );

                document.body.style.overflow =
                    '';

                video.pause();

                video.currentTime = 0;    

            }

            cards.forEach(card => {

                card.addEventListener(
                    'click',
                    () => {

                        openModal(card);

                    }
                );

            });

            closeButton.addEventListener(
                'click',
                closeModal
            );

            modal.addEventListener(
                'click',
                function(event){

                    if (
                        event.target === modal
                    ) {

                        closeModal();

                    }

                }
            );

            document.addEventListener(
                'keydown',
                function(event){

                    if (

                        event.key === 'Escape'

                        &&

                        modal.classList.contains(
                            'show'
                        )

                    ) {

                        closeModal();

                    }

                }
            );

        }

        /*
        -------------------------------------------------------
        Recover Account Button
        -------------------------------------------------------
        */

        const recoverButton =
            document.getElementById(
                'recoverAccountButton'
            );

        if (recoverButton) {

            recoverButton.addEventListener(
                'click',
                function(){

                    if (modal) {

                        modal.classList.remove(
                            'show'
                        );

                    }

                    document.body.style.overflow =
                        '';

                    const forgotButton =
                        document.getElementById(
                            'openForgotPasswordModal'
                        );

                    if (forgotButton) {

                        forgotButton.click();

                    }

                }
            );

        }

        /*
        -------------------------------------------------------
        Counter Animation
        -------------------------------------------------------
        */

        const counters =
            document.querySelectorAll(
                '.counter'
            );

        counters.forEach(counter => {

            const animate = () => {

                const target =
                    Number(
                        counter.dataset.target
                    );

                const current =
                    Number(
                        counter.textContent
                    );

                const increment =
                    Math.max(
                        1,
                        Math.ceil(target / 80)
                    );

                if (
                    current < target
                ) {

                    counter.textContent =
                        Math.min(
                            current + increment,
                            target
                        );

                    requestAnimationFrame(
                        animate
                    );

                }

            };

            animate();

        });

        /*
        -------------------------------------------------------
        Scroll Reveal
        -------------------------------------------------------
        */

        const reveals =
            document.querySelectorAll(
                '.reveal'
            );

        const observer =
            new IntersectionObserver(

                entries => {

                    entries.forEach(entry => {

                        if (
                            entry.isIntersecting
                        ) {

                            entry.target.classList.add(
                                'active'
                            );

                        }

                    });

                },

                {

                    threshold: 0.15

                }

            );

        reveals.forEach(element => {

            observer.observe(
                element
            );

        });

    }
);
