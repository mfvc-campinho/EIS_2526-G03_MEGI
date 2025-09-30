// Definir atributos
// Filtrar para aparecer apenas o da coleção correta

const events = [
        {
        "id_event": 1,
        "name": "Reading Session",
        "description": "Reading Session of book management information systems.",
        "category": "Exhibition",
        "date": "2023-06-15",
        "location": "Lisbon",
        "rate": 1
    },
            {
        "id_event": 2,
        "name": "Exhibition of Mona Lisa",
        "description": "Art Work",
        "category": "Exhibition",
        "date": "2023-06-15",
        "location": "Porto",
        "rate": 1
    }
   ]

const container = document.getElementById("event-container");

// From items, for each was added to appear all the list

events.forEach(event_var => {
    const card = document.createElement("div");

    // Info of Event
    const infoEvent = document.createElement("div");
    infoEvent.innerHTML = `
        <h2>${event_var.name}</h2>
        <p><strong>Description:</strong> ${event_var.description}</p>
        <p><strong>Category:</strong> <a href="collection_page.html">${event_var.category}</a></p>
        <p><strong>Location:</strong> ${event_var.location}</p>
        <p><strong>Date:</strong> ${event_var.date}</p>
        <p><strong>Rate:</strong> ${event_var.rate}</p>
        <hr>
    `;

    // Update
    const updateEvent = document.createElement("div");
    updateEvent.innerHTML = `
       <p><strong><a href="home_page.html">Click here to Update Event</a></strong></p>
       <hr>
    `;

    // Delete
    const deleteEvent = document.createElement("div");
    deleteEvent.innerHTML = `
       <p><strong><a href="home_page.html">Click here to Delete Event</a></strong></p>
       <hr>
    `;

    // Alert (In process...)
    const alertEvent = document.createElement("div");
    alertEvent.innerHTML = `
       <p><strong>Event is near!</strong></p>
       <hr>
    `;

   // Add all the divisions to the same card
    card.appendChild(infoEvent);
    card.appendChild(updateEvent);
    card.appendChild(deleteEvent);
    card.appendChild(alertEvent);

    // Add card to container
    container.appendChild(card);
});