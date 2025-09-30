const items = [
        {
        "id_item": 1,
        "name": "Management Information Systems",
        "description": "Book by Kenneth C. Laudon on management information systems.",
        "category": "Book",
        "image": "",
        "acquisition_date": "2023-06-15",
        "cost": 45.90,
        "state": "Used",
        "location": "Shelf 1, Box B",
        "stastica1":"",
        "stastica2":""
    },
        {
        "id_item": 2,
        "name": "JavaScript: The Definitive Guide",
        "description": "Comprehensive JavaScript reference written by David Flanagan.",
        "category": "Book",
        "image": "",
        "acquisition_date": "2023-07-20",
        "cost": 55.00,
        "state": "New",
        "location": "Shelf 2, Box C",
        "stastica1":"",
        "stastica2":""
    }
    ]


// Get the divisoria already created on item_page
const container = document.getElementById("book-container")

// Present book and create card for posterior CSS
const book = items[0] // In this moment, it will appear always the first on the const items, but this will be dynamic 
const card = document.createElement("div");

// Div Image
const imageDiv =document.createElement("div")
imageDiv.innerHTML = `
    <img src="${book.image}" alt="${book.name}">
    <hr></hr>
`;


// Div info
const infoDiv = document.createElement("div");
infoDiv.innerHTML = `

    <h2>${book.name}</h2>
    <p><strong>Author:</strong> ${book.author}</p>
    <p><strong>Category:</strong> <a href="collection_page.html">${book.category}</a></p>
    <p><strong>Cost:</strong> $${book.cost}</p>
    <p><strong>State:</strong> ${book.state}</p>
    <p><strong>Location:</strong> ${book.location}</p>
    <hr></hr>
`;

//Div Stastica
const statsDiv = document.createElement("div");
statsDiv.innerHTML = `
   <p><strong>Stastica 1:</strong> ${book.stastica1}</p>
   <p><strong>Stastica 2:</strong> ${book.stastica2}</p>
   <hr></hr>
`;

// Add 3 divisions to card
card.appendChild(imageDiv);
card.appendChild(infoDiv);
card.appendChild(statsDiv);

// Add cards to container
container.appendChild(card);


