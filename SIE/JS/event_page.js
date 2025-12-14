gcInitScrollRestore({
      key: 'gc-scroll-events',
      formSelector: '#events-filters-form'
    });

// Modal de detalhes do evento
    (function() {
      const modal = document.getElementById('event-modal');
      if (!modal) return;

      const ratingsContainer = document.getElementById('modal-ratings');

      function appendStars(container, value) {
        if (typeof value !== 'number' || Number.isNaN(value)) return;
        for (let i = 1; i <= 5; i++) {
          const icon = document.createElement('i');
          if (value >= i) {
            icon.className = 'bi bi-star-fill';
          } else if (value >= i - 0.5) {
            icon.className = 'bi bi-star-half';
          } else {
            icon.className = 'bi bi-star';
          }
          container.appendChild(icon);
        }
      }

      function normalizeRatingValue(value) {
        if (typeof value === 'number' && !Number.isNaN(value)) {
          return value;
        }
        const parsed = parseInt(value, 10);
        return Number.isFinite(parsed) ? parsed : null;
      }

      function fillUserRatingRow(row, value, isSaved) {
        if (!row) return;
        const label = row.querySelector('.label');
        const starsHolder = row.querySelector('.user-rating-stars');
        if (!label || !starsHolder) return;
        starsHolder.innerHTML = '';
        const numericValue = normalizeRatingValue(value);
        if (!numericValue) {
          if (row.dataset.initiallyHidden === 'true') {
            row.hidden = true;
          } else {
            row.hidden = false;
          }
          row.classList.remove('pending');
          label.textContent = 'Your rating:';
          return;
        }
        row.hidden = false;
        appendStars(starsHolder, numericValue);
        if (isSaved) {
          row.classList.remove('pending');
          label.textContent = 'Your rating:';
        } else {
          row.classList.add('pending');
          label.textContent = 'Your rating (not saved yet):';
        }
      }

      if (ratingsContainer) {
        ratingsContainer.addEventListener('change', function(e) {
          const select = e.target;
          if (!select.matches('.modal-rating-form select')) return;
          const card = select.closest('.modal-rating-card');
          if (!card) return;
          const userRow = card.querySelector('.modal-rating-user');
          if (!userRow) return;
          const numericValue = normalizeRatingValue(select.value);
          if (numericValue) {
            fillUserRatingRow(userRow, numericValue, false);
          } else {
            const saved = normalizeRatingValue(userRow.dataset.savedValue || '');
            fillUserRatingRow(userRow, saved, true);
          }
        });
      }

      function addMessage(text) {
        if (!ratingsContainer) return;
        const message = document.createElement('div');
        message.className = 'modal-rating-message';
        message.textContent = text;
        ratingsContainer.appendChild(message);
      }

      function renderRatings(payload) {
        if (!ratingsContainer) return;
        ratingsContainer.innerHTML = '';

        if (!payload) {
          addMessage('This event has no rating data yet.');
          return;
        }

        const eventAverage = typeof payload.eventAverage === 'number' ? payload.eventAverage : null;
        const eventAverageCount = typeof payload.eventAverageCount === 'number' ? payload.eventAverageCount : 0;
        const collections = Array.isArray(payload.collections) ? payload.collections : [];
        const isPast = !!payload.isPast;
        const hasRsvp = !!payload.hasRsvp;
        const canRate = !!payload.canRate;

        if (eventAverage) {
          const overview = document.createElement('div');
          overview.className = 'modal-rating-overview';
          const left = document.createElement('span');
          left.textContent = 'Average event rating';
          overview.appendChild(left);
          const right = document.createElement('span');
          right.className = 'rating-badge';
          appendStars(right, eventAverage);
          const avgValue = document.createElement('span');
          avgValue.className = 'avg-value';
          avgValue.textContent = `${eventAverage.toFixed(1)}/5`;
          right.appendChild(avgValue);
          const count = document.createElement('span');
          count.className = 'count';
          count.textContent = `(${eventAverageCount})`;
          right.appendChild(count);
          overview.appendChild(right);
          ratingsContainer.appendChild(overview);
        }

        const messages = [];
        if (!isPast) {
          messages.push('This event has not happened yet. Ratings become available after the date.');
        }
        if (isPast && !hasRsvp) {
          messages.push('Only participants who RSVP can rate the collections.');
        }
        if (collections.length === 0) {
          messages.push('This event has no collections associated for rating.');
        }
        messages.forEach(addMessage);

        if (collections.length === 0) {
          return;
        }

        const statsWrapper = document.createElement('div');
        statsWrapper.className = 'modal-rating-stats';

        collections.forEach(function(item) {
          const card = document.createElement('div');
          card.className = 'modal-rating-card';

          const header = document.createElement('div');
          header.className = 'modal-rating-card-header';

          const name = document.createElement('span');
          name.className = 'collection-name';
          name.textContent = item.name || 'Collection';
          header.appendChild(name);

          const badge = document.createElement('span');
          badge.className = 'rating-badge';
          if (typeof item.average === 'number' && !Number.isNaN(item.average) && (item.count || 0) > 0) {
            appendStars(badge, item.average);
            const avgValue = document.createElement('span');
            avgValue.className = 'avg-value';
            avgValue.textContent = `${item.average.toFixed(1)}/5`;
            badge.appendChild(avgValue);
            const badgeCount = document.createElement('span');
            badgeCount.className = 'count';
            badgeCount.textContent = `(${item.count})`;
            badge.appendChild(badgeCount);
          } else {
            badge.textContent = 'There are no ratings yet';
          }
          header.appendChild(badge);
          card.appendChild(header);

          const userHasRating = typeof item.userRating === 'number' && !Number.isNaN(item.userRating);

          if (canRate) {
            const form = document.createElement('form');
            form.className = 'modal-rating-form';
            form.method = 'POST';
            form.action = 'events_action.php';

            const actionField = document.createElement('input');
            actionField.type = 'hidden';
            actionField.name = 'action';
            actionField.value = 'rate';
            form.appendChild(actionField);

            const eventField = document.createElement('input');
            eventField.type = 'hidden';
            eventField.name = 'id';
            eventField.value = payload.eventId || '';
            form.appendChild(eventField);

            const collectionField = document.createElement('input');
            collectionField.type = 'hidden';
            collectionField.name = 'collection_id';
            collectionField.value = item.id || '';
            form.appendChild(collectionField);

            const label = document.createElement('label');
            label.textContent = 'Avaliar:';
            label.style.fontWeight = '600';
            label.style.fontSize = '0.9rem';
            label.style.color = '#374151';
            form.appendChild(label);

            const select = document.createElement('select');
            select.name = 'rating';
            select.setAttribute('aria-label', `Rating for ${item.name || 'the collection'}`);

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select...';
            select.appendChild(placeholder);

            for (let i = 1; i <= 5; i++) {
              const option = document.createElement('option');
              option.value = String(i);
              option.textContent = `${i} stars`;
              if (userHasRating && item.userRating === i) {
                option.selected = true;
              }
              select.appendChild(option);
            }

            form.appendChild(select);

            const button = document.createElement('button');
            button.type = 'submit';
            button.className = 'explore-btn small';
            button.textContent = 'Save';
            form.appendChild(button);

            card.appendChild(form);
          }

          if (canRate || userHasRating) {
            const userRow = document.createElement('div');
            userRow.className = 'modal-rating-user';
            if (item.id) {
              userRow.dataset.collectionId = item.id;
            }
            const label = document.createElement('span');
            label.className = 'label';
            label.textContent = 'Your rating:';
            userRow.appendChild(label);
            const starHolder = document.createElement('span');
            starHolder.className = 'user-rating-stars';
            userRow.appendChild(starHolder);
            userRow.dataset.savedValue = userHasRating ? String(item.userRating) : '';
            userRow.dataset.initiallyHidden = userHasRating ? 'false' : 'true';
            if (userHasRating) {
              fillUserRatingRow(userRow, item.userRating, true);
            } else {
              userRow.hidden = true;
            }
            card.appendChild(userRow);
          }

          statsWrapper.appendChild(card);
        });

        ratingsContainer.appendChild(statsWrapper);
      }

      const eventCards = document.querySelectorAll('.js-event-card');

      eventCards.forEach(function(card) {
        card.addEventListener('click', function(e) {
          const interactive = e.target.closest('a, button, form, select, input, textarea');
          if (interactive && card.contains(interactive)) return;

          const name = card.getAttribute('data-name') || '';
          const summary = card.getAttribute('data-summary') || '';
          const description = card.getAttribute('data-description') || '';
          const date = card.getAttribute('data-date') || '';
          const time = card.getAttribute('data-time') || '';
          const combined = card.getAttribute('data-datetime') || '';
          const location = card.getAttribute('data-location') || '';
          const type = card.getAttribute('data-type') || '';
          const cost = card.getAttribute('data-cost') || '';
          const ratingRaw = card.getAttribute('data-rating') || '';

          document.getElementById('modal-title').textContent = name;
          document.getElementById('modal-type').textContent = type;
          document.getElementById('modal-summary').textContent = summary;
          document.getElementById('modal-description').textContent = description;
          const modalDate = document.getElementById('modal-date');
          if (modalDate) {
            modalDate.textContent = date || combined;
          }
          const modalTimeRow = document.getElementById('modal-time-row');
          const modalTime = document.getElementById('modal-time');
          if (modalTimeRow && modalTime) {
            if (time) {
              modalTimeRow.hidden = false;
              modalTime.textContent = time;
            } else {
              modalTimeRow.hidden = true;
              modalTime.textContent = '';
              if (modalDate && combined && !date) {
                modalDate.textContent = combined;
              }
            }
          }
          const locationLink = document.getElementById('modal-location');
          if (locationLink) {
            const cleanLocation = (location || '').trim();
            const hasLocation = cleanLocation.length > 0;
            locationLink.textContent = hasLocation ? cleanLocation : 'Location unavailable';
            if (hasLocation) {
              locationLink.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(cleanLocation);
              locationLink.classList.remove('disabled');
              locationLink.setAttribute('aria-label', 'Open ' + cleanLocation + ' on Google Maps');
              locationLink.setAttribute('target', '_blank');
              locationLink.setAttribute('rel', 'noopener noreferrer');
            } else {
              locationLink.removeAttribute('href');
              locationLink.removeAttribute('aria-label');
              locationLink.removeAttribute('target');
              locationLink.removeAttribute('rel');
              locationLink.classList.add('disabled');
            }
          }

          const costEl = document.getElementById('modal-cost');
          if (costEl) {
            costEl.textContent = cost || 'Free entrance';
          }

          let payload = null;
          if (ratingRaw) {
            try {
              payload = JSON.parse(ratingRaw);
            } catch (err) {
              payload = null;
            }
          }

          renderRatings(payload);
          modal.classList.add('open');
        });
      });

      // Fechar ao clicar fora do modal
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          modal.classList.remove('open');
        }
      });

      // Fechar com ESC
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('open')) {
          modal.classList.remove('open');
        }
      });
    })();

    // Calendar functionality
    (function() {
      const calendarView = document.getElementById('calendar-view');
      const calendarGrid = document.getElementById('calendar-grid');
      const calendarMonthYear = document.getElementById('calendar-month-year');
      const calendarToggleBtn = document.getElementById('calendar-toggle-btn');
      const calendarToggleText = document.getElementById('calendar-toggle-text');
      const calendarPrevBtn = document.getElementById('calendar-prev');
      const calendarTodayBtn = document.getElementById('calendar-today');
      const calendarNextBtn = document.getElementById('calendar-next');

      const monthsPT = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
      const daysPT = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

      let currentDate = new Date();
      const cfg = window.eventPageData || {};
      let allEvents = Array.isArray(cfg.events) ? cfg.events : [];
      const canCreateEvents = !!cfg.canCreateEvents;
      const createAllowedFromDate = cfg.createAllowedFromDate || '';
      const eventsFormUrl = cfg.eventsFormUrl || 'events_form.php';

      function parseEventDate(dateStr) {
        if (!dateStr) return null;
        const date = new Date((dateStr || '').replace(' ', 'T'));
        return isNaN(date.getTime()) ? null : date;
      }

      function renderCalendar(year, month) {
        calendarGrid.innerHTML = '';
        calendarMonthYear.textContent = `${monthsPT[month]} ${year}`;

        // Day headers
        daysPT.forEach(day => {
          const header = document.createElement('div');
          header.className = 'calendar-day-header';
          header.textContent = day;
          calendarGrid.appendChild(header);
        });

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const firstDayOfWeek = firstDay.getDay();
        const daysInMonth = lastDay.getDate();

        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;

        // Empty cells for days before month starts
        for (let i = 0; i < firstDayOfWeek; i++) {
          const emptyDay = document.createElement('div');
          emptyDay.className = 'calendar-day empty';
          calendarGrid.appendChild(emptyDay);
        }

        // Days of the month
        for (let day = 1; day <= daysInMonth; day++) {
          const dayCell = document.createElement('div');
          dayCell.className = 'calendar-day';
          
          if (isCurrentMonth && day === today.getDate()) {
            dayCell.classList.add('today');
          }

          const dayNumber = document.createElement('div');
          dayNumber.className = 'calendar-day-number';
          dayNumber.textContent = day;
          dayCell.appendChild(dayNumber);

          // Find events for this day
          const dayDate = new Date(year, month, day);
          const dayDateStr = dayDate.toISOString().split('T')[0];

          const dayEvents = allEvents.reduce((list, evt) => {
            const evtDate = parseEventDate(evt.date);
            if (!evtDate) return list;
            const evtDateStr = evtDate.toISOString().split('T')[0];
            if (evtDateStr === dayDateStr) {
              list.push({ data: evt, parsedDate: evtDate, dateStr: evtDateStr });
            }
            return list;
          }, []);

          if (canCreateEvents && dayDateStr >= createAllowedFromDate) {
            dayCell.classList.add('can-create');
            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'calendar-add-btn';
            const labelDate = dayDate.toLocaleDateString('pt-PT', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            addBtn.setAttribute('aria-label', `Criar evento em ${labelDate}`);
            addBtn.innerHTML = '<i class="bi bi-plus-lg"></i>';
            addBtn.addEventListener('click', function(e) {
              e.stopPropagation();
              const params = new URLSearchParams({ date: dayDateStr });
              window.location.href = `${eventsFormUrl}?${params.toString()}`;
            });
            dayCell.appendChild(addBtn);
          }

          dayEvents.forEach(({ data, parsedDate, dateStr }) => {
            const eventItem = document.createElement('div');
            eventItem.className = 'calendar-event-item';
            const isUpcomingEvent = dateStr >= todayStr;
            const hasUserRsvp = !!data.hasUserRsvp;
            if (isUpcomingEvent) {
              eventItem.classList.add('upcoming');
              if (hasUserRsvp) {
                eventItem.classList.add('rsvp');
              }
            } else {
              eventItem.classList.add('past');
            }
            
            const timeSpan = document.createElement('span');
            timeSpan.className = 'calendar-event-time';
            timeSpan.textContent = parsedDate.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
            eventItem.appendChild(timeSpan);

            const nameSpan = document.createElement('span');
            nameSpan.className = 'calendar-event-name';
            nameSpan.textContent = data.name || 'Evento';
            nameSpan.title = data.name || 'Evento';
            eventItem.appendChild(nameSpan);

            eventItem.addEventListener('click', function() {
              // Find the corresponding button in the grid and trigger click
              const buttons = document.querySelectorAll('.js-view-event');
              buttons.forEach(btn => {
                if (btn.getAttribute('data-name') === data.name) {
                  btn.click();
                }
              });
            });

            dayCell.appendChild(eventItem);
          });

          calendarGrid.appendChild(dayCell);
        }
      }

      renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
      calendarView.classList.add('show');
      calendarToggleBtn.classList.add('active');
      calendarToggleText.textContent = 'Hide Calendar';
      calendarToggleBtn.setAttribute('aria-expanded', 'true');

      calendarToggleBtn.addEventListener('click', function() {
        const isVisible = calendarView.classList.contains('show');
        if (isVisible) {
          calendarView.classList.remove('show');
          calendarToggleBtn.classList.remove('active');
          calendarToggleText.textContent = 'Show Calendar';
          calendarToggleBtn.setAttribute('aria-expanded', 'false');
        } else {
          calendarView.classList.add('show');
          calendarToggleBtn.classList.add('active');
          calendarToggleText.textContent = 'Hide Calendar';
          calendarToggleBtn.setAttribute('aria-expanded', 'true');
          renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
        }
      });

      calendarPrevBtn.addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
      });

      calendarTodayBtn.addEventListener('click', function() {
        currentDate = new Date();
        renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
      });

      calendarNextBtn.addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
      });
    })();
