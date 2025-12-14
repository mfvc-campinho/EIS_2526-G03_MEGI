gcInitScrollRestore({
                key: 'gc-scroll-user',
                formSelector: '#user-top-filters'
            });
            document.addEventListener('DOMContentLoaded', function() {
                var cards = document.querySelectorAll('.collection-card-link');
                cards.forEach(function(card) {
                    var href = card.getAttribute('data-collection-link');
                    if (!href) return;
                    card.addEventListener('click', function(e) {
                        if (e.target.closest('a, button')) {
                            return;
                        }
                        window.location.href = href;
                    });
                    card.addEventListener('keydown', function(e) {
                        if (e.target !== card) return;
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            window.location.href = href;
                        }
                    });
                });
            });
