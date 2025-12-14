-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 05-Dez-2025 às 00:04
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sie_db`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `collections`
--

CREATE TABLE `collections` (
  `collection_id` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `user_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `collections`
--

INSERT INTO `collections` (`collection_id`, `name`, `type`, `cover_image`, `summary`, `description`, `created_at`, `user_id`) VALUES
('escudos', 'Portuguese Escudos', 'Coins', 'uploads/collections/coins.png', 'A journey through Portugal’s historical coins.', 'This collection showcases Portugal’s numismatic legacy, featuring original Escudo coins minted before the euro era. It highlights their unique designs, materials, and historical significance in the country’s economy.', '2018-04-10 00:00:00', 'cristina_feira'),
('escudos-gold', 'Golden Escudos Vault', 'Coins', 'uploads/collections/gold_coins.jpg', 'Handpicked gold Escudos from the monarchy to mid-century republic.', 'Focuses on premium gold-minted Escudo coins, documenting mint marks, alloys, and historical context tied to Portugal\'s treasury reforms.', '2020-11-18 00:00:00', 'cristina_feira'),
('jerseys', 'Autographed Portuguese Football Jerseys', 'Sports Memorabilia', 'uploads/collections/img_6931ad16abe3c.jpg', 'Signed memorabilia from legendary players.', 'An exclusive selection of football jerseys autographed by renowned athletes from Portuguese and international teams. Each item tells a story of sporting triumph, teamwork, and fan devotion.', '2019-06-25 00:00:00', 'rui_frio'),
('pokemon', 'Pokémon Trading Cards', 'Collectible Cards', 'uploads/collections/img_6931acf789b9a.jpg', 'Rare and classic cards from the Pokémon universe.', 'A comprehensive Pokémon TCG collection featuring rare holographic cards, first editions, and limited releases from various generations. It celebrates both the nostalgic and competitive sides of Pokémon collecting.', '2021-04-20 00:00:00', 'rui_frio'),
('ballon-dor', 'Ballon d''Or Collection', 'Trophies', 'https://upload.wikimedia.org/wikipedia/en/thumb/e/ea/Ballon_d%27Or.png/220px-Ballon_d%27Or.png', 'My legendary Ballon d''Or trophies.', 'A collection of all my Ballon d''Or awards, representing years of dedication, hard work, and incredible performances on the pitch. Each trophy tells the story of a season filled with goals, assists, and unforgettable moments. SIUUU!', '2019-03-15 00:00:00', 'cristiano_pato'),
('luxury-cars', 'Luxury Car Collection', 'Vehicles', 'https://cdn.motor1.com/images/mgl/pb3Kze/s1/cristiano-ronaldo-bugatti-chiron.jpg', 'The finest supercars and luxury vehicles.', 'From Bugattis to Ferraris, this collection showcases the most exclusive and powerful cars in the world. Each vehicle represents the pinnacle of automotive engineering and luxury design.', '2020-06-10 00:00:00', 'cristiano_pato'),
('football-boots', 'Match-Worn Football Boots', 'Sports Memorabilia', 'https://media.gq-magazine.co.uk/photos/645b5c8c8223e5c3801bde9c/16:9/w_2560%2Cc_limit/cristiano-ronaldo-boots-hp.jpg', 'Iconic boots from historic matches.', 'A collection of my match-worn football boots from Champions League finals, World Cups, and record-breaking performances. Each pair has a special story and represents moments of glory.', '2021-01-20 00:00:00', 'cristiano_pato'),
('tactical-boards', 'Tactical Analysis Boards', 'Sports Equipment', 'https://www.thesun.co.uk/wp-content/uploads/2023/09/andre-villas-boas-coach-chelsea-836089039.jpg', 'My collection of tactical boards from winning seasons.', 'These tactical boards were used during championship-winning campaigns and historic victories. Each board contains the strategic formations and game plans that led to success on the pitch.', '2020-09-05 00:00:00', 'andre_villas'),
('trophy-cabinet', 'Trophy Cabinet', 'Trophies', 'https://i2-prod.football.london/incoming/article18665755.ece/ALTERNATES/s1200c/0_GettyImages-1201765141.jpg', 'Trophies from Premier League, Europa League, and more.', 'A comprehensive collection of trophies won throughout my managerial career, including the Europa League with Porto, Premier League titles, and various domestic cups. Each trophy represents tactical excellence and team dedication.', '2019-11-12 00:00:00', 'andre_villas'),
('signed-books', 'Signed Football Strategy Books', 'Books', 'https://www.thesun.co.uk/wp-content/uploads/2023/11/premier-league-manchester-city-v-872694422.jpg', 'Rare signed editions from legendary managers.', 'A collection of football strategy and tactics books signed by legendary managers like José Mourinho, Sir Alex Ferguson, and Pep Guardiola. These books contain the wisdom and philosophies that shaped modern football.', '2021-03-08 00:00:00', 'andre_villas'),
('csgo-skins', 'Elite CS:GO Skins Collection', 'Gaming Items', 'https://static1.thegamerimages.com/wordpress/wp-content/uploads/2024/01/csgo-knives.jpg', 'The rarest and most expensive CS:GO weapon skins.', 'An elite collection of Counter-Strike: Global Offensive weapon skins, featuring rare knives, Dragon Lore AWPs, and limited edition StatTrak items. Each skin is a work of digital art and represents thousands of hours of gameplay and trading.', '2021-07-15 00:00:00', 'zorlak');

-- --------------------------------------------------------

--
-- Estrutura da tabela `collection_events`
--

CREATE TABLE `collection_events` (
  `collection_id` varchar(100) NOT NULL,
  `event_id` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `collection_events`
--

INSERT INTO `collection_events` (`collection_id`, `event_id`) VALUES
('escudos', 'escudos-event-1'),
('escudos', 'escudos-event-2'),
('jerseys', 'jerseys-event-1'),
('jerseys', 'jerseys-event-2'),
('pokemon', 'pokemon-event-1'),
('pokemon', 'pokemon-event-2');

-- --------------------------------------------------------

--
-- Estrutura da tabela `collection_items`
--

CREATE TABLE `collection_items` (
  `collection_id` varchar(100) NOT NULL,
  `item_id` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `collection_items`
--

INSERT INTO `collection_items` (`collection_id`, `item_id`) VALUES
('escudos', 'escudos-item-1'),
('escudos', 'escudos-item-2'),
('escudos-gold', 'escudos-item-1'),
('jerseys', 'jerseys-item-1'),
('jerseys', 'jerseys-item-2'),
('jerseys', 'pokemon-item-1'),
('pokemon', 'jerseys-item-1'),
('pokemon', 'jerseys-item-2'),
('pokemon', 'pokemon-item-1'),
('pokemon', 'pokemon-item-2');

-- --------------------------------------------------------

--
-- Estrutura da tabela `events`
--

CREATE TABLE `events` (
  `event_id` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `localization` varchar(255) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `host_user_id` varchar(100) DEFAULT NULL,
  `collection_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `events`
--

INSERT INTO `events` (`event_id`, `name`, `localization`, `event_date`, `type`, `summary`, `description`, `cost`, `created_at`, `updated_at`, `host_user_id`, `collection_id`) VALUES
('escudos-event-1', 'Lisbon Numismatic Fair', 'Lisbon', '2025-12-12', 'fair', 'Annual showcase for Iberian coins, rare notes, and appraisal sessions.', 'Dealers and historians gather to trade Escudo-era coins, host restoration demos, and discuss preservation techniques for metallic currencies.', 7.50, '2025-09-01 00:00:00', '2025-11-13 00:00:00', NULL, 'escudos'),
('escudos-event-2', 'Coin Acquisition Meetup', 'Porto', '2025-05-10', 'meetup', 'Small-group meetup focused on sourcing missing Escudo variants.', 'Collectors swap duplicates, share leads for reputable sellers, and review authentication tips for mid-century Portuguese currency.', 0.00, '2025-02-15 00:00:00', '2025-04-15 00:00:00', NULL, 'escudos'),
('jerseys-event-1', 'Autograph Session 2025', 'Porto Stadium', '2025-06-01', 'signing', 'Pitch-side autograph session with national league legends.', 'Participants bring authenticated jerseys for signatures, while equipment managers talk about fabric care for long-term display.', 15.00, '2025-02-28 00:00:00', '2025-04-30 00:00:00', NULL, 'jerseys'),
('jerseys-event-2', 'Collectors’ Expo 2025', 'Lisbon', '2025-09-12', 'expo', 'Large expo covering game-worn memorabilia and restoration services.', 'Workshops detail how to certify match-used kits, remove stains without damaging signatures, and insure valuable memorabilia.', 12.00, '2025-05-05 00:00:00', '2025-07-18 00:00:00', NULL, 'jerseys'),
('pokemon-event-1', 'Pokémon Expo 2025', 'Tokyo', '2025-03-10', 'expo', 'Global expo highlighting competitive decks and newly graded grails.', 'Includes PSA grading booths, artist signings, and a showcase of legendary cards from the Kanto through Paldea releases.', 20.00, '2024-12-01 00:00:00', '2025-02-01 00:00:00', NULL, 'pokemon'),
('pokemon-event-2', 'Trading Card Convention', 'London', '2025-05-01', 'convention', 'European convention dedicated to rare pulls, auctions, and live trades.', 'Vendors curate showcase cases for first editions, while panels cover long-term storage, pricing data, and authenticity checks.', 18.00, '2025-01-20 00:00:00', '2025-03-15 00:00:00', NULL, 'pokemon'),
('ballon-dor-gala', 'Ballon d''Or Gala 2026', 'Paris', '2026-01-15', 'gala', 'Exclusive gala celebrating football excellence.', 'The annual Ballon d''Or ceremony where the world''s best players gather to celebrate another year of incredible football. Red carpet, champagne, and the golden ball trophy presentation. SIUUU!', 250.00, '2025-10-01 00:00:00', '2025-12-01 00:00:00', 'cristiano_pato', 'ballon-dor'),
('supercar-expo', 'Monaco Supercar Expo', 'Monte Carlo', '2026-05-20', 'expo', 'The most exclusive supercar exhibition in Europe.', 'A gathering of the world''s most expensive and rare supercars. From Bugatti Chirons to limited edition Ferraris, this expo showcases automotive perfection. Private viewings, test drives, and exclusive auctions.', 150.00, '2025-11-10 00:00:00', '2025-12-05 00:00:00', 'cristiano_pato', 'luxury-cars'),
('uefa-legends-match', 'UEFA Legends Charity Match', 'Madrid', '2026-03-25', 'match', 'Charity football match with football legends.', 'An unforgettable charity match bringing together football legends from across Europe. All proceeds go to children''s foundations. Expect spectacular goals, incredible skills, and lots of nostalgia!', 35.00, '2025-12-01 00:00:00', '2025-12-10 00:00:00', 'cristiano_pato', 'football-boots'),
('tactics-workshop', 'Advanced Football Tactics Workshop', 'London', '2026-02-14', 'workshop', 'Intensive tactical analysis workshop for coaches.', 'A comprehensive workshop covering modern football tactics, set-piece strategies, and player positioning. Includes video analysis sessions, tactical board demonstrations, and Q&A with experienced managers.', 85.00, '2025-11-15 00:00:00', '2025-12-08 00:00:00', 'andre_villas', 'tactical-boards'),
('europa-reunion', 'Europa League Winners Reunion', 'Porto', '2026-04-10', 'reunion', 'Reunion of the 2011 Europa League winning squad.', 'A special reunion celebrating the historic Europa League victory with FC Porto. Meet the players, relive the memories, and see the trophy up close. Includes behind-the-scenes stories and tactical breakdown of key matches.', 25.00, '2025-12-05 00:00:00', '2025-12-12 00:00:00', 'andre_villas', 'trophy-cabinet'),
('csgo-major', 'CS:GO Major Tournament', 'Berlin', '2026-06-15', 'tournament', 'The biggest Counter-Strike tournament of the year.', 'Watch the world''s best CS:GO teams compete for glory and massive prize pools. Exclusive skin drops, meet professional players, and experience the intensity of competitive gaming. Viewer pass includes in-game souvenir cases.', 45.00, '2025-12-01 00:00:00', '2025-12-13 00:00:00', 'zorlak', 'csgo-skins'),
('skin-trading-fair', 'International Skin Trading Fair', 'Stockholm', '2026-08-22', 'fair', 'The ultimate CS:GO skin trading event.', 'Meet traders, collectors, and enthusiasts from around the world. Trade rare skins, get expert appraisals, and discover the latest market trends. Includes workshops on skin investment strategies and fraud prevention.', 0.00, '2025-12-10 00:00:00', '2025-12-13 00:00:00', 'zorlak', 'csgo-skins');

-- --------------------------------------------------------

--
-- Estrutura da tabela `event_ratings`
--

CREATE TABLE `event_ratings` (
  `id` int(11) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `collection_id` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `event_ratings`
--

INSERT INTO `event_ratings` (`id`, `event_id`, `user_id`, `rating`, `collection_id`, `created_at`, `updated_at`) VALUES
(1, 'pokemon-event-1', 'rui_frio', 5, 'pokemon', '2025-12-04 19:32:46', '2025-12-04 19:32:46'),
(2, 'pokemon-event-2', 'rui_frio', 3, 'pokemon', '2025-12-04 19:33:26', '2025-12-04 19:33:26');

-- --------------------------------------------------------

--
-- Estrutura da tabela `event_rsvps`
--

CREATE TABLE `event_rsvps` (
  `id` int(11) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `rsvp_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `event_rsvps`
--

INSERT INTO `event_rsvps` (`id`, `event_id`, `user_id`, `rsvp_at`) VALUES
(7, 'escudos-event-1', 'rui_frio', '2025-12-04 19:38:42');

-- --------------------------------------------------------

--
-- Estrutura da tabela `items`
--

CREATE TABLE `items` (
  `item_id` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `importance` enum('Low','Medium','High','Very High') DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `collection_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `items`
--

INSERT INTO `items` (`item_id`, `name`, `importance`, `weight`, `price`, `acquisition_date`, `created_at`, `updated_at`, `image`, `collection_id`) VALUES
('escudos-item-1', '1950 Escudo', 'High', 4.500, 120.00, '2020-03-15', '2020-03-01 00:00:00', '2020-03-15 00:00:00', 'uploads/collections/escudo1950.jpg', 'escudos'),
('escudos-item-2', '1960 Escudo', 'Very High', 5.100, 250.00, '2021-07-10', '2021-06-20 00:00:00', '2021-07-10 00:00:00', 'uploads/collections/escudo1960.jpg', 'escudos'),
('jerseys-item-1', 'Deco\'s FC Porto 2004 Jersey', 'High', 0.400, 450.00, '2020-03-12', '2020-02-20 00:00:00', '2020-03-12 00:00:00', 'uploads/items/img_6931b7b33f416.jpg', 'pokemon'),
('jerseys-item-2', 'Cardozo\'s Benfica 2010 Jersey', 'High', 0.400, 400.00, '2021-09-03', '2021-08-10 00:00:00', '2021-09-03 00:00:00', '../images/benfica.jpg', 'pokemon'),
('pokemon-item-1', 'Pikachu Base Set', 'Low', 0.005, 150.00, '2021-06-18', '2021-06-01 00:00:00', '2021-06-18 00:00:00', '../images/pikachuset.JPG', 'pokemon'),
('pokemon-item-2', 'Charizard Holo', 'Very High', 0.005, 2000.00, '2022-04-22', '2022-03-30 00:00:00', '2022-04-22 00:00:00', '../images/charizard.jpg', 'pokemon');

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `user_id` varchar(100) NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_photo` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `member_since` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`user_id`, `user_name`, `user_photo`, `date_of_birth`, `email`, `password`, `member_since`) VALUES
('cristina_feira', 'Cristina Feira', '../images/cristina.jpg', '1985-05-20', 'collector.main@email.com', 'password123', '2015'),
('rui_frio', 'Rui Frio', '../images/rui.jpg', '1982-07-14', 'rui.frio@email.com', '$2y$10$A.gKXDnJhsg30zRhRhfBtet0vEK4JNdySm/0gjZCLGgsXEkpiImJe', '2018'),
('cristiano_pato', 'Cristiano Pato Donaldo', 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8c/Cristiano_Ronaldo_2018.jpg/220px-Cristiano_Ronaldo_2018.jpg', '1985-02-05', 'cr7pato@email.com', 'password123', '2019'),
('andre_villas', 'André Villas Todas Boas', 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e9/Andr%C3%A9_Villas-Boas_2012.jpg/220px-Andr%C3%A9_Villas-Boas_2012.jpg', '1977-10-17', 'avb@email.com', 'password123', '2020'),
('zorlak', 'Zorlak', 'https://i.pinimg.com/736x/c0/25/13/c02513f8b00f99e2d99c9e6b55f2d503.jpg', '1995-08-22', 'zorlak@email.com', 'password123', '2021');

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_followers`
--

CREATE TABLE `user_followers` (
  `follower_id` varchar(100) NOT NULL,
  `following_id` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_liked_collections`
--

CREATE TABLE `user_liked_collections` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `liked_collection_id` varchar(255) NOT NULL,
  `last_updated` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `user_liked_collections`
--

INSERT INTO `user_liked_collections` (`id`, `user_id`, `liked_collection_id`, `last_updated`) VALUES
(2, 'rui_frio', 'jerseys', '2025-12-04 12:18:59'),
(3, 'rui_frio', 'pokemon', '2025-12-04 12:18:59');

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_liked_items`
--

CREATE TABLE `user_liked_items` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `liked_item_id` varchar(255) NOT NULL,
  `last_updated` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `user_liked_items`
--

INSERT INTO `user_liked_items` (`id`, `user_id`, `liked_item_id`, `last_updated`) VALUES
(5, 'rui_frio', 'jerseys-item-1', '2025-12-03 22:53:16'),
(6, 'rui_frio', 'jerseys-item-2', '2025-12-03 22:53:16'),
(7, 'rui_frio', 'escudos-item-1', '2025-12-04 20:02:46');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`collection_id`),
  ADD KEY `idx_collections_owner` (`user_id`);

--
-- Índices para tabela `collection_events`
--
ALTER TABLE `collection_events`
  ADD PRIMARY KEY (`collection_id`,`event_id`),
  ADD KEY `fk_ce_event` (`event_id`);

--
-- Índices para tabela `collection_items`
--
ALTER TABLE `collection_items`
  ADD PRIMARY KEY (`collection_id`,`item_id`),
  ADD KEY `fk_ci_item` (`item_id`);

--
-- Índices para tabela `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_events_host` (`host_user_id`),
  ADD KEY `idx_events_collection` (`collection_id`);

--
-- Índices para tabela `event_ratings`
--
ALTER TABLE `event_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_user_collection` (`event_id`,`user_id`,`collection_id`),
  ADD KEY `idx_event_ratings_collection` (`collection_id`);

--
-- Índices para tabela `event_rsvps`
--
ALTER TABLE `event_rsvps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_rsvp` (`event_id`,`user_id`);

--
-- Índices para tabela `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_items_collection` (`collection_id`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `user_followers`
--
ALTER TABLE `user_followers`
  ADD PRIMARY KEY (`follower_id`,`following_id`),
  ADD KEY `following_id` (`following_id`);

--
-- Índices para tabela `user_liked_collections`
--
ALTER TABLE `user_liked_collections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_collection` (`user_id`,`liked_collection_id`);

--
-- Índices para tabela `user_liked_items`
--
ALTER TABLE `user_liked_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_item` (`user_id`,`liked_item_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `event_ratings`
--
ALTER TABLE `event_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `event_rsvps`
--
ALTER TABLE `event_rsvps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `user_liked_collections`
--
ALTER TABLE `user_liked_collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `user_liked_items`
--
ALTER TABLE `user_liked_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `collections`
--
ALTER TABLE `collections`
  ADD CONSTRAINT `fk_collections_owner` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `collection_events`
--
ALTER TABLE `collection_events`
  ADD CONSTRAINT `fk_ce_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ce_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `collection_items`
--
ALTER TABLE `collection_items`
  ADD CONSTRAINT `fk_ci_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ci_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`),
  ADD CONSTRAINT `fk_events_host` FOREIGN KEY (`host_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `fk_items_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `user_followers`
--
ALTER TABLE `user_followers`
  ADD CONSTRAINT `user_followers_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_followers_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
