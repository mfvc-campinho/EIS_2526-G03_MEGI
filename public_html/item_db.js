// Neste momento, vou deixar a base de dados aqui, para ser mais fácil, mas perguntar, se o fetch pode ser usado
// Falta Json para selecionar a coleção correspondente
// Falta pop ups, a dizer de certeza?

const items = [
        {
        "id_item": 1,
        "name": "Management Information Systems",
        "description": "Book by Kenneth C. Laudon on management information systems.",
        "author": "Kenneth C. Laudon",
        "category": "Book",
        "image": "",
        "acquisition_date": "2023-06-15",
        "cost": 45.90,
        "state": "Used",
        "location": "Shelf 1, Box B",
        "stastica1":"",
        "stastica2":"",
    },
        {
        "id_item": 2,
        "name": "JavaScript: The Definitive Guide",
        "description": "Comprehensive JavaScript reference written by David Flanagan.",
        "author": "David Flanagan",
        "category": ["Book", "Informatics"],
        "image": "",
        "acquisition_date": "2023-07-20",
        "cost": 55.00,
        "state": "New",
        "location": "Shelf 2, Box C",
        "stastica1":"",
        "stastica2":""
    }
]


// Get the div already created on item_page
const container = document.getElementById("item-container")

// Present item and create card for posterior CSS
const item_var = items[0] // In this moment, it will appear always the first on the const items, but this will be dynamic 
const card = document.createElement("div");

// Create Divisions

// Div Image
const imageItem =document.createElement("div")
imageItem.innerHTML = `
    <img src="${item_var.image}" alt="${item_var.name}">
    <hr>
`;

// Info from item
const infoItem = document.createElement("div");
infoItem.innerHTML = `

    <h2>${item_var.name}</h2>
    <p><strong>Author:</strong> ${item_var.author}</p>
    <p><strong>Category:</strong> <a href="collection_page.html">${item_var.category}</a></p>
    <p><strong>Cost:</strong> $${item_var.cost}</p>
    <p><strong>State:</strong> ${item_var.state}</p>
    <p><strong>Location:</strong> ${item_var.location}</p>
    <hr>
`;

// Stastics from item
const statsItem = document.createElement("div");
statsItem.innerHTML = `
   <p><strong>Stastica 1:</strong> ${item_var.stastica1}</p>
   <p><strong>Stastica 2:</strong> ${item_var.stastica2}</p>
   <hr>
`;

// Update
const updateItem = document.createElement("div");
updateItem.innerHTML = `
   <p><strong> <a href="home_page.html"> Click here to Update Item [ERROR - Discuss next steps] </a> </strong> </p>
   <hr>
`;

// Delete
const deleteItem = document.createElement("div");
deleteItem.innerHTML = `
   <p><strong> <a href="home_page.html"> Click here to Delete Item [ERROR - Discuss next steps] </a> </strong> </p>
   <hr>
`;

// Add all the divisions to the same card
card.appendChild(imageItem);
card.appendChild(infoItem);
card.appendChild(statsItem);
card.appendChild(updateItem)
card.appendChild(deleteItem)

// Add card to container
container.appendChild(card);


