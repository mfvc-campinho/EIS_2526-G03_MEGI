-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 03-Dez-2025 às 23:22
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
('escudos', 'Portuguese Escudos', 'Coins', '../images/coins.png', 'A journey through Portugal’s historical coins.', 'This collection showcases Portugal’s numismatic legacy, featuring original Escudo coins minted before the euro era. It highlights their unique designs, materials, and historical significance in the country’s economy.', '2018-04-10 00:00:00', 'cristina_feira'),
('escudos-gold', 'Golden Escudos Vault', 'Coins', '../images/gold_coins.jpg', 'Handpicked gold Escudos from the monarchy to mid-century republic.', 'Focuses on premium gold-minted Escudo coins, documenting mint marks, alloys, and historical context tied to Portugal\'s treasury reforms.', '2020-11-18 00:00:00', 'cristina_feira'),
('jerseys', 'Autographed Portuguese Football Jerseys', 'Sports Memorabilia', '../images/benfica.jpg', 'Signed memorabilia from legendary players.', 'An exclusive selection of football jerseys autographed by renowned athletes from Portuguese and international teams. Each item tells a story of sporting triumph, teamwork, and fan devotion.', '2019-06-25 00:00:00', 'rui_frio'),
('pokemon', 'Pokémon Trading Cards', 'Collectible Cards', '../images/pikachuset.jpg', 'Rare and classic cards from the Pokémon universe.', 'A comprehensive Pokémon TCG collection featuring rare holographic cards, first editions, and limited releases from various generations. It celebrates both the nostalgic and competitive sides of Pokémon collecting.', '2021-04-20 00:00:00', 'rui_frio');

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
  `event_date` date DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `host_user_id` varchar(100) DEFAULT NULL,
  `collection_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `events`
--

INSERT INTO `events` (`event_id`, `name`, `localization`, `event_date`, `type`, `summary`, `description`, `created_at`, `updated_at`, `host_user_id`, `collection_id`) VALUES
('escudos-event-1', 'Lisbon Numismatic Fair', 'Lisbon', '2025-12-12', 'fair', 'Annual showcase for Iberian coins, rare notes, and appraisal sessions.', 'Dealers and historians gather to trade Escudo-era coins, host restoration demos, and discuss preservation techniques for metallic currencies.', '2025-09-01 00:00:00', '2025-11-13 00:00:00', NULL, 'escudos'),
('escudos-event-2', 'Coin Acquisition Meetup', 'Porto', '2025-05-10', 'meetup', 'Small-group meetup focused on sourcing missing Escudo variants.', 'Collectors swap duplicates, share leads for reputable sellers, and review authentication tips for mid-century Portuguese currency.', '2025-02-15 00:00:00', '2025-04-15 00:00:00', NULL, 'escudos'),
('jerseys-event-1', 'Autograph Session 2025', 'Porto Stadium', '2025-06-01', 'signing', 'Pitch-side autograph session with national league legends.', 'Participants bring authenticated jerseys for signatures, while equipment managers talk about fabric care for long-term display.', '2025-02-28 00:00:00', '2025-04-30 00:00:00', NULL, 'jerseys'),
('jerseys-event-2', 'Collectors’ Expo 2025', 'Lisbon', '2025-09-12', 'expo', 'Large expo covering game-worn memorabilia and restoration services.', 'Workshops detail how to certify match-used kits, remove stains without damaging signatures, and insure valuable memorabilia.', '2025-05-05 00:00:00', '2025-07-18 00:00:00', NULL, 'jerseys'),
('pokemon-event-1', 'Pokémon Expo 2025', 'Tokyo', '2025-03-10', 'expo', 'Global expo highlighting competitive decks and newly graded grails.', 'Includes PSA grading booths, artist signings, and a showcase of legendary cards from the Kanto through Paldea releases.', '2024-12-01 00:00:00', '2025-02-01 00:00:00', NULL, 'pokemon'),
('pokemon-event-2', 'Trading Card Convention', 'London', '2025-05-01', 'convention', 'European convention dedicated to rare pulls, auctions, and live trades.', 'Vendors curate showcase cases for first editions, while panels cover long-term storage, pricing data, and authenticity checks.', '2025-01-20 00:00:00', '2025-03-15 00:00:00', NULL, 'pokemon');

-- --------------------------------------------------------

--
-- Estrutura da tabela `event_ratings`
--

CREATE TABLE `event_ratings` (
  `event_id` varchar(100) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `collection_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `event_ratings`
--

INSERT INTO `event_ratings` (`event_id`, `user_id`, `rating`, `collection_id`) VALUES
('escudos-event-1', 'cristina_feira', 5, NULL),
('escudos-event-1', 'rui_frio', NULL, NULL),
('escudos-event-2', 'cristina_feira', NULL, NULL),
('escudos-event-2', 'rui_frio', 5, NULL),
('jerseys-event-1', 'rui_frio', NULL, NULL),
('jerseys-event-2', 'rui_frio', 5, NULL),
('pokemon-event-1', 'cristina_feira', 5, NULL),
('pokemon-event-1', 'rui_frio', 5, NULL),
('pokemon-event-2', 'rui_frio', 5, NULL);

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
('escudos-item-1', '1950 Escudo', 'High', 4.500, 120.00, '2020-03-15', '2020-03-01 00:00:00', '2020-03-15 00:00:00', '../images/escudo1950.jpg', 'escudos'),
('escudos-item-2', '1960 Escudo', 'Very High', 5.100, 250.00, '2021-07-10', '2021-06-20 00:00:00', '2021-07-10 00:00:00', '../images/escudo1960.jpg', 'escudos'),
('jerseys-item-1', 'Deco\'s FC Porto 2004 Jersey', 'High', 0.400, 450.00, '2020-03-12', '2020-02-20 00:00:00', '2020-03-12 00:00:00', '../images/porto.jpg', 'pokemon'),
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
('rui_frio', 'Rui Frio', '../images/rui.jpg', '1982-07-14', 'rui.frio@email.com', '$2y$10$F61AxUcubEgZ61FVB8IXWe3u7rScokh4ojE2XMVvbzBeNLC.HAagC', '2018');

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_event_ratings`
--

CREATE TABLE `user_event_ratings` (
  `id` int(11) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `user_event_ratings`
--

INSERT INTO `user_event_ratings` (`id`, `event_id`, `user_id`, `rating`, `created_at`, `updated_at`) VALUES
(1, 'pokemon-event-1', 'rui_frio', 5, '2025-12-03 22:09:25', '2025-12-03 22:09:25'),
(2, 'escudos-event-2', 'rui_frio', 5, '2025-12-03 22:09:50', '2025-12-03 22:09:50'),
(3, 'jerseys-event-2', 'rui_frio', 4, '2025-12-03 22:09:53', '2025-12-03 22:09:53'),
(4, 'pokemon-event-2', 'rui_frio', 4, '2025-12-03 22:09:55', '2025-12-03 22:12:45');

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

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_liked_events`
--

CREATE TABLE `user_liked_events` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `liked_event_id` varchar(255) NOT NULL,
  `last_updated` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(6, 'rui_frio', 'jerseys-item-2', '2025-12-03 22:53:16');

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
  ADD PRIMARY KEY (`event_id`,`user_id`),
  ADD KEY `fk_eu_user` (`user_id`);

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
-- Índices para tabela `user_event_ratings`
--
ALTER TABLE `user_event_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_user` (`event_id`,`user_id`);

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
-- Índices para tabela `user_liked_events`
--
ALTER TABLE `user_liked_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_event` (`user_id`,`liked_event_id`);

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
-- AUTO_INCREMENT de tabela `user_event_ratings`
--
ALTER TABLE `user_event_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `user_liked_collections`
--
ALTER TABLE `user_liked_collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `user_liked_events`
--
ALTER TABLE `user_liked_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `user_liked_items`
--
ALTER TABLE `user_liked_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  ADD CONSTRAINT `fk_events_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_events_host` FOREIGN KEY (`host_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `event_ratings`
--
ALTER TABLE `event_ratings`
  ADD CONSTRAINT `fk_eu_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
