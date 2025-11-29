<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', '1');

$pageTitle = 'Collection — GoodCollections';

include __DIR__ . '/header.php';
?>

    <!-- ===============================
         MAIN CONTENT
    =============================== -->
    <main>
        <!--  Breadcrumb -->
        <nav class="breadcrumb-nav" aria-label="Breadcrumb">
            <ol class="breadcrumb-list">
                <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
                <li class="breadcrumb-item"><a href="all_collections.php">Collections</a></li>
                <li class="breadcrumb-item" aria-current="page">
                    <span id="collection-breadcrumb-name">Collection</span>
                </li>
            </ol>
        </nav>

        <!-- Page Title (preenchido também por JS) -->
        <h1 id="collection-title" class="page-title">Collection</h1>

        <!-- Botão voltar -->
        <div class="center-block">
            <a href="javascript:history.back()" class="explore-btn ghost centered-btn">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to Collections
            </a>
        </div>

        <!-- Botões de ação -->
        <section id="buttons-bar">
            <h2 class="sr-only">Collection actions</h2>

            <button id="edit-collection" class="explore-btn warning" data-requires-login>
                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                Edit Collection
            </button>

            <button id="add-item" class="explore-btn success" data-requires-login>
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>
                Add Item
            </button>
        </section>

        <!-- Filtros de itens -->
        <section class="filter-section items-filter" aria-label="Filter collection items">
            <div class="filter-control">
                <label for="itemsFilter" class="filter-label">
                    <i class="bi bi-funnel me-1" aria-hidden="true"></i>
                    Show
                </label>
                <div class="filter-input">
                    <select id="itemsFilter" class="filter-select">
                        <option value="all">All items</option>
                        <option value="liked">Liked by me</option>
                        <option value="mostLiked">Most liked</option>
                        <option value="recent">Recently acquired</option>
                    </select>
                    <button id="resetItemsFilter" class="filter-clear" type="button" title="Reset filters">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="filter-control">
                <label for="importanceFilter" class="filter-label">
                    <i class="bi bi-badge-ad me-1" aria-hidden="true"></i>
                    Importance
                </label>
                <div class="filter-input">
                    <select id="importanceFilter" class="filter-select">
                        <option value="all">All importances</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Very High">Very High</option>
                    </select>
                </div>
            </div>

            <div class="filter-control">
                <label for="priceFilter" class="filter-label">
                    <i class="bi bi-cash-stack me-1" aria-hidden="true"></i>
                    Price
                </label>
                <div class="filter-input">
                    <select id="priceFilter" class="filter-select">
                        <option value="all">All prices</option>
                        <option value="budget">Budget (&lt; EUR 100)</option>
                        <option value="mid">Mid (EUR 100-500)</option>
                        <option value="premium">Premium (&gt; EUR 500)</option>
                    </select>
                </div>
            </div>

            <p id="items-filter-note" class="filter-note" aria-live="polite"></p>
        </section>

        <!-- Grid de itens (preenchido por JS) -->
        <div class="list-pagination" data-pagination-for="collection-items">
            <label class="page-size-label">
                Ver
                <select data-page-size>
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="50">50</option>
                </select>
                itens de cada vez
            </label>
            <div class="pagination-actions">
                <button type="button" class="explore-btn ghost icon-only" data-page-prev aria-label="Ver anteriores">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <span class="pagination-status" data-pagination-status>Mostrando 0-0 de 0</span>
                <button type="button" class="explore-btn ghost icon-only" data-page-next aria-label="Ver seguintes">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <section class="collection-container" id="collection-items">
            <h2 class="sr-only">Collection items</h2>
        </section>

        <!-- Estatísticas da coleção -->
        <section id="collection-stats" aria-label="Collection statistics">
            <h2 class="section-subtitle">Collection Statistics</h2>
            <div class="stats-grid">
                <article class="stat-card" data-key="totalItems">
                    <h3 class="sr-only">Total items statistic</h3>
                    <div class="stat-icon"><i class="bi bi-list"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">-</div>
                        <div class="stat-label">Total items</div>
                    </div>
                </article>

                <article class="stat-card" data-key="totalValue">
                    <h3 class="sr-only">Total estimated value statistic</h3>
                    <div class="stat-icon"><i class="bi bi-currency-euro"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">-</div>
                        <div class="stat-label">Total estimated value (€)</div>
                    </div>
                </article>

                <article class="stat-card" data-key="avgWeight">
                    <h3 class="sr-only">Average item weight statistic</h3>
                    <div class="stat-icon"><i class="bi bi-123"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">-</div>
                        <div class="stat-label">Average item weight (g)</div>
                    </div>
                </article>

                <article class="stat-card" data-key="linkedEvents">
                    <h3 class="sr-only">Linked events statistic</h3>
                    <div class="stat-icon"><i class="bi bi-calendar-event"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">-</div>
                        <div class="stat-label">Linked events</div>
                    </div>
                </article>

                <article class="stat-card" data-key="oldestItem">
                    <h3 class="sr-only">Oldest item statistic</h3>
                    <div class="stat-icon"><i class="bi bi-calendar2-minus"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">-</div>
                        <div class="stat-label">Oldest item (acquired)</div>
                    </div>
                </article>

                <article class="stat-card" data-key="newestItem">
                    <h3 class="sr-only">Newest item statistic</h3>
                    <div class="stat-icon"><i class="bi bi-calendar2-plus"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">-</div>
                        <div class="stat-label">Newest item (acquired)</div>
                    </div>
                </article>
            </div>
        </section>

        <!-- Card de overview da coleção -->
        <section class="collection-hero">
            <h2 class="section-subtitle sr-only">Collection overview</h2>
            <div id="collection-meta">
                <img id="owner-photo" src="images/user.jpg" alt="Owner photo">
                <div id="meta-text">
                    <p>
                        <strong>Owner:</strong>
                        <span id="owner-name"></span>
                    </p>
                    <p>
                        <strong>Created:</strong>
                        <span id="creation-date"></span>
                    </p>
                    <p>
                        <strong>Type:</strong>
                        <span id="type">-</span>
                    </p>
                    <p>
                        <strong>Items:</strong>
                        <span id="items-count">-</span>
                    </p>
                    <p id="description-line">
                        <strong>Description:</strong>
                        <span id="description">-</span>
                    </p>
                </div>
            </div>
        </section>

        <!-- Eventos relacionados -->
        <section class="collection-events">
            <h2 class="section-subtitle">Collection Events</h2>
            <div id="collection-events" class="collection-events-list">
                <p class="notice-message">Loading linked events...</p>
            </div>
        </section>
    </main>

    <!-- ===============================
         MODAL: ADD / EDIT ITEM
         =============================== -->
    <div id="item-modal" class="modal">
        <div class="modal-content">
            <span id="close-modal" class="close">&times;</span>
            <h2 id="modal-title">Add Item</h2>

            <!-- AGORA a enviar para handle_item.php -->
            <form id="item-form" action="handle_item.php" method="post">
                <input type="hidden" id="item-id" name="item-id">

                <div class="form-section form-section--required">
                    <h3>Required fields</h3>
                    <label for="item-name">Name:</label>
                    <input type="text" id="item-name" name="item-name" required>

                    <label for="item-price">Price (€):</label>
                    <input type="number" id="item-price" name="item-price" step="0.01" required>

                    <label for="item-collections">Collections:</label>
                    <select id="item-collections" name="item-collections[]" multiple required></select>
                    <small class="form-hint">
                        Hold Ctrl (Windows) or Cmd (Mac) to select multiple collections.
                    </small>
                </div>

                <div class="form-section form-section--optional">
                    <h3>Optional fields</h3>
                    <label for="item-importance">Importance:</label>
                    <input type="text" id="item-importance" name="item-importance">

                    <label for="item-weight">Weight (g):</label>
                    <input type="number" id="item-weight" name="item-weight" step="0.01">

                    <label for="item-date">Acquisition Date:</label>
                    <input type="date" id="item-date" name="item-date">

                    <label for="item-image">Image (URL):</label>
                    <input type="text" id="item-image" name="item-image" placeholder="images/default.jpg">
                </div>

                <div class="modal-actions crud-controls">
                    <button type="submit" class="explore-btn success">
                        <i class="bi bi-save me-1" aria-hidden="true"></i> Save
                    </button>
                    <button type="button" id="cancel-modal" class="explore-btn danger delete-btn">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===============================
         MODAL: EDIT COLLECTION
         =============================== -->
    <div id="collection-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="close-collection-modal">&times;</span>
            <h2 id="collection-modal-title">Edit Collection</h2>

            <!-- Usa o handler que já tens para coleções -->
            <form id="form-collection" action="handle_collection.php" method="post">
                <input type="hidden" id="collection-id" name="collection-id" />

                <div class="form-section form-section--required">
                    <h3>Required fields</h3>
                    <label for="col-name">Name:</label>
                    <input type="text" id="col-name" name="col-name" required />
                    <label for="col-summary">Summary:</label>
                    <textarea id="col-summary" name="col-summary" required></textarea>
                </div>

                <div class="form-section form-section--optional">
                    <h3>Optional fields</h3>
                    <label for="col-description">Full Description:</label>
                    <textarea id="col-description" name="col-description"></textarea>
                    <label for="col-image">Image (URL):</label>
                    <input type="text" id="col-image" name="col-image" placeholder="images/default.jpg" />
                    <label for="col-type">Type:</label>
                    <input type="text" id="col-type" name="col-type" />
                </div>

                <div class="modal-actions">
                    <button type="submit" class="explore-btn success">
                        <i class="bi bi-save me-1" aria-hidden="true"></i> Save
                    </button>
                    <button type="button" id="cancel-collection-modal" class="explore-btn danger">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modais de forgot-password e add-account
         (se usares estes aqui como no home_page.php) -->
    <div id="forgot-password-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="close-forgot-modal">&times;</span>
            <h2>Forgot Password</h2>
            <form id="form-forgot-password">
                <p>Enter your email address and we'll send you a link to reset your password.</p>
                <label for="forgot-email">Email:</label>
                <input type="email" id="forgot-email" name="forgot-email" required />
                <div class="modal-actions">
                    <button type="submit" class="save-btn">Send Reset Link</button>
                </div>
            </form>
        </div>
    </div>

    <div id="add-account-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="close-account-modal">&times;</span>
            <h2>Create New Account</h2>

            <form id="form-add-account">
                <label for="acc-name">Username:</label>
                <input type="text" id="acc-name" name="acc-name" required />
                <label for="acc-owner-photo">Photo URL:</label>
                <input type="url" id="acc-owner-photo" name="acc-owner-photo"
                       placeholder="https://example.com/photo.jpg" />
                <label for="acc-dob">Date of Birth:</label>
                <input type="date" id="acc-dob" name="acc-dob" />
                <label for="acc-member-since">Member Since (YYYY):</label>
                <input type="number" id="acc-member-since" name="acc-member-since" min="1900" max="2100" step="1"
                       placeholder="2020" />
                <label for="acc-email">Email:</label>
                <input type="email" id="acc-email" name="acc-email" required />
                <label for="acc-password">Password:</label>
                <input type="password" id="acc-password" name="acc-password" required />
                <label for="acc-password-confirm">Confirm Password:</label>
                <input type="password" id="acc-password-confirm" name="acc-password-confirm" required />
                <div class="modal-actions">
                    <button type="submit" class="save-btn">Create Account</button>
                </div>
            </form>
        </div>
    </div>

<?php
include __DIR__ . '/footer.php';
?>
