-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 14-Dez-2025 às 19:34
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
('col-693ee8636259f', 'Portuguese Escudos', 'Coins', 'uploads/collections/img_693ee863626e3.png', 'A journey through Portugal’s historical coins.', 'This collection showcases Portugal’s numismatic legacy, featuring original Escudo coins minted before the euro era. It highlights their unique designs, materials, and historical significance in the country’s economy.', '2025-12-14 16:40:03', 'u_693ee7cb6fc2a3.09874157'),
('col-693ee8aecbe89', 'Golden Escudos Vault', 'Coins', 'uploads/collections/img_693ee8aecbf9a.jpg', 'Handpicked gold Escudos from the monarchy to mid-century republic.', 'Focuses on premium gold-minted Escudo coins, documenting mint marks, alloys, and historical context tied to Portugal\'s treasury reforms.', '2025-12-14 16:41:18', 'u_693ee7cb6fc2a3.09874157'),
('col-693eea001f7c9', 'Autographed Portuguese Football Jerseys', 'Other', 'uploads/collections/img_693eea001f838.jpg', 'Signed memorabilia from legendary players.', 'An exclusive selection of football jerseys autographed by renowned athletes from Portuguese and international teams. Each item tells a story of sporting triumph, teamwork, and fan devotion.', '2025-12-14 16:46:56', 'u_693ee96c3c0318.74718994'),
('col-693eea2cbf602', 'Pokémon Trading Cards', 'Collectible Cards', 'uploads/collections/img_693eea2cbf6e6.jpg', 'Rare and classic cards from the Pokémon universe.', 'A comprehensive Pokémon TCG collection featuring rare holographic cards, first editions, and limited releases from various generations. It celebrates both the nostalgic and competitive sides of Pokémon collecting.', '2025-12-14 16:47:40', 'u_693ee96c3c0318.74718994'),
('col-693eeaf72fd9d', 'Vintage Mechanical Watches', 'Toys & Figures', 'uploads/collections/img_693eeaf72fe11.jpg', 'Classic mechanical watches from the golden age of horology.', 'A curated collection of vintage mechanical watches from renowned Swiss and European manufacturers. Each piece reflects the craftsmanship, precision engineering, and timeless design that defined luxury watchmaking throughout the 20th century.', '2025-12-14 16:51:03', 'u_693eea932ce4e0.57322484'),
('col-693eeb2c71266', 'Contemporary Art Prints', 'Board Games', 'uploads/collections/img_693eeb2c71330.jpg', 'Limited edition prints by contemporary artists.', 'This collection brings together limited edition art prints from emerging and established contemporary artists. It explores modern themes such as identity, technology, and urban life, blending artistic expression with collectible value.', '2025-12-14 16:51:56', 'u_693eea932ce4e0.57322484'),
('col-693eec276b541', 'Classic Vinyl Records', 'Board Games', 'uploads/collections/img_693eec276b63a.jpg', 'Iconic vinyl albums from rock, jazz, and soul legends.', 'A nostalgic collection of classic vinyl records featuring legendary artists and albums that shaped music history. From jazz improvisations to rock anthems, this collection celebrates the warmth and authenticity of analog sound.', '2025-12-14 16:56:07', 'u_693eea932ce4e0.57322484'),
('col-693eed01aa06d', 'Luxury Fountain Pens', 'Other', 'uploads/collections/img_693eed01aa0b9.jpg', 'Elegant pens crafted for precision and style.', 'A refined collection of luxury fountain pens from prestigious brands. Featuring precious materials and meticulous craftsmanship, these pens embody the art of handwriting and timeless elegance.', '2025-12-14 16:59:45', 'u_693eeca0932690.97050524'),
('col-700010aaa1111', 'Limited Edition Pens', 'Other', NULL, 'Limited and numbered fountain pens.', 'A curated collection of rare and limited edition fountain pens.', '2025-12-14 18:05:40', 'u_693eeca0932690.97050524'),
('col-700010bbb2222', 'Calligraphy Essentials', 'Other', NULL, 'Essential tools for calligraphy.', 'Collection focused on tools and materials for calligraphy practice.', '2025-12-14 18:05:40', 'u_693eeca0932690.97050524'),
('col-700040aaa1111', 'Vintage Mechanical Watches', 'Other', NULL, 'Classic mechanical watches.', 'Collection of vintage mechanical watches from renowned European manufacturers.', '2025-12-14 18:09:53', 'u_693eeca0932690.97050524'),
('col-700040bbb2222', 'Luxury Writing Accessories', 'Other', NULL, 'Luxury accessories for writing.', 'Accessories such as inks, cases and maintenance tools for luxury writing instruments.', '2025-12-14 18:09:53', 'u_693eeca0932690.97050524'),
('col-700040ccc3333', 'Modern Art Prints', 'Other', 'uploads/collections/img_693f02b1a75a2.jpg', 'Contemporary art prints.', 'Limited edition modern and contemporary art prints.', '2025-12-14 18:09:53', 'u_693eeca0932690.97050524');

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
('col-693eed01aa06d', 'evt-693ef07607878'),
('col-693eed01aa06d', 'evt-693ef1c2efe8d'),
('col-693eed01aa06d', 'evt-700030aaa1111'),
('col-700010aaa1111', 'evt-700030aaa1111'),
('col-700010aaa1111', 'evt-700030bbb2222'),
('col-700010aaa1111', 'evt-700060bbb2222'),
('col-700010bbb2222', 'evt-700030bbb2222'),
('col-700040aaa1111', 'evt-700060aaa1111'),
('col-700040bbb2222', 'evt-700060bbb2222'),
('col-700040ccc3333', 'evt-700060ccc3333');

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
('col-693eed01aa06d', 'item-693ef331a8110'),
('col-693eed01aa06d', 'item-700020aaa1111'),
('col-700010aaa1111', 'item-700020aaa1111'),
('col-700010aaa1111', 'item-700050bbb2222'),
('col-700010bbb2222', 'item-700020bbb2222'),
('col-700040aaa1111', 'item-700050aaa1111'),
('col-700040bbb2222', 'item-700050bbb2222'),
('col-700040ccc3333', 'item-700050ccc3333');

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
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `events`
--

INSERT INTO `events` (`event_id`, `name`, `localization`, `event_date`, `type`, `summary`, `description`, `created_at`, `updated_at`, `cost`) VALUES
('evt-693ef07607878', 'International Fountain Pen Expo', 'Milan', '2024-10-01 10:00:00', '', '', '', '2025-12-14 17:14:30', NULL, 25.00),
('evt-693ef1c2efe8d', 'Calligraphy & Luxury Writing Workshop', 'Paris, France', '2026-03-21 10:30:00', '', '', '', '2025-12-14 17:20:02', NULL, 45.00),
('evt-700030aaa1111', 'International Pen Collectors Fair', 'Rome, Italy', '2025-10-18 11:00:00', 'Exhibition', 'Large international meeting of pen collectors.', 'An international fair gathering collectors, brands and experts in luxury writing instruments.', '2025-12-14 18:05:40', '2025-12-14 18:05:40', 25.00),
('evt-700030bbb2222', 'Beginner Calligraphy Workshop', 'Porto, Portugal', '2026-01-22 09:30:00', '', 'Introductory calligraphy workshop.', 'Hands-on workshop focused on calligraphy fundamentals and pen handling.', '2025-12-14 18:05:40', '2025-12-14 18:05:40', 15.00),
('evt-700060aaa1111', 'Vintage Watch Collectors Meetup', 'Zurich, Switzerland', '2025-09-12 18:00:00', 'Meetup', 'Private meetup for vintage watch collectors.', 'An exclusive gathering of collectors focused on vintage mechanical watches.', '2025-12-14 18:09:53', '2025-12-14 18:09:53', 40.00),
('evt-700060bbb2222', 'Luxury Ink & Accessories Showcase', 'Paris, France', '2025-11-05 15:00:00', 'Exhibition', 'Showcase of luxury inks and writing accessories.', 'Exhibition highlighting premium inks, cases and writing accessories.', '2025-12-14 18:09:53', '2025-12-14 18:09:53', 20.00),
('evt-700060ccc3333', 'Modern Art Prints Exhibition', 'Berlin, Germany', '2026-03-18 10:00:00', 'Exhibition', 'Exhibition of modern art prints.', 'A curated exhibition of contemporary and modern art prints.', '2025-12-14 18:09:53', '2025-12-14 18:09:53', 30.00);

-- --------------------------------------------------------

--
-- Estrutura da tabela `event_ratings`
--

CREATE TABLE `event_ratings` (
  `id` int(11) NOT NULL,
  `event_id` varchar(100) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `collection_id` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `event_ratings`
--

INSERT INTO `event_ratings` (`id`, `event_id`, `user_id`, `rating`, `collection_id`, `created_at`, `updated_at`) VALUES
(1, 'evt-693ef07607878', 'u_693eeca0932690.97050524', 5, 'col-693eed01aa06d', '2025-12-14 17:34:20', '2025-12-14 17:34:20'),
(5, 'evt-700030aaa1111', 'u_693eeca0932690.97050524', 5, 'col-693eed01aa06d', '2025-12-14 18:05:40', '2025-12-14 18:05:40'),
(6, 'evt-700030aaa1111', 'u_700001aabbccdd.11111111', 4, 'col-700010aaa1111', '2025-12-14 18:05:40', '2025-12-14 18:05:40'),
(8, 'evt-700060aaa1111', 'u_693eeca0932690.97050524', 5, 'col-700040aaa1111', '2025-12-14 18:09:53', '2025-12-14 18:09:53');

-- --------------------------------------------------------

--
-- Estrutura da tabela `event_rsvps`
--

CREATE TABLE `event_rsvps` (
  `id` int(11) NOT NULL,
  `event_id` varchar(100) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `rsvp_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `event_rsvps`
--

INSERT INTO `event_rsvps` (`id`, `event_id`, `user_id`, `rsvp_at`) VALUES
(1, 'evt-693ef1c2efe8d', 'u_693eeca0932690.97050524', '2025-12-14 17:27:56'),
(2, 'evt-693ef07607878', 'u_693eeca0932690.97050524', '2025-12-14 17:33:28'),
(6, 'evt-700030aaa1111', 'u_693eeca0932690.97050524', '2025-12-14 18:05:40'),
(7, 'evt-700030aaa1111', 'u_700001aabbccdd.11111111', '2025-12-14 18:05:40'),
(8, 'evt-700030bbb2222', 'u_700002bbccddee.22222222', '2025-12-14 18:05:40'),
(9, 'evt-700060aaa1111', 'u_693eeca0932690.97050524', '2025-12-14 18:09:53'),
(10, 'evt-700060bbb2222', 'u_700001aabbccdd.11111111', '2025-12-14 18:09:53'),
(11, 'evt-700060ccc3333', 'u_700002bbccddee.22222222', '2025-12-14 18:09:53');

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
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `items`
--

INSERT INTO `items` (`item_id`, `name`, `importance`, `weight`, `price`, `acquisition_date`, `created_at`, `updated_at`, `image`) VALUES
('item-693ef331a8110', 'Montblanc Meisterstück 149', '', 0.000, 0.00, '2021-05-12', '2025-12-14 17:26:09', '2025-12-14 17:26:39', 'uploads/items/items-693ef34f6ac39-OIP__4_.webp'),
('item-700020aaa1111', 'Sailor King of Pen', 'Very High', 0.035, 780.00, '2021-09-15', '2025-12-14 18:05:40', '2025-12-14 18:05:40', NULL),
('item-700020bbb2222', 'Lamy Dialog CC', 'High', 0.028, 320.00, '2023-04-10', '2025-12-14 18:05:40', '2025-12-14 18:05:40', NULL),
('item-700050aaa1111', 'Omega Seamaster 1965', 'Very High', 0.120, 4200.00, '2019-06-18', '2025-12-14 18:09:53', '2025-12-14 18:09:53', NULL),
('item-700050bbb2222', 'Iroshizuku Kon-Peki Ink', 'Medium', 0.050, 28.00, '2024-02-10', '2025-12-14 18:09:53', '2025-12-14 18:09:53', NULL),
('item-700050ccc3333', 'Limited Edition Abstract Print', 'High', 0.800, 650.00, '2022-11-03', '2025-12-14 18:09:53', '2025-12-14 18:09:53', NULL);

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
('u_693ee7cb6fc2a3.09874157', 'Cristina Feira', 'uploads/users/img_693ee7cb6358e5.74532857.jpg', '1985-05-20', 'cristina.feira@gmail.com', '$2y$10$VrQ2j2wW80iRduk4iaBgg.2UEnEYCa4ZLnlz59albf6FSxEN2maYO', '2025'),
('u_693ee96c3c0318.74718994', 'Rui Frio', 'uploads/users/img_693ee99a88c589.27200553.webp', '0000-00-00', 'rui.frio@email.com', '$2y$10$53SmMjMoxMs3fpaHsUMVEuiDWTZwnWoExxDjtgJTaseG7UBAwfRK.', '2025'),
('u_693eea932ce4e0.57322484', 'Cristiano', 'uploads/users/img_693eea93207f49.34635092.webp', '2000-02-05', 'cr7pato@email.com', '$2y$10$iXHCUq03Ll6.LxHsCO/XcerowUPRVnK/IJjUIS8Q9tLt9vl9Rx.o2', '2025'),
('u_693eeca0932690.97050524', 'Mariana Lopes', 'uploads/users/img_693eeca086a713.26943978.webp', '1991-09-14', 'mariana.lopes@email.com', '$2y$10$M.4Vj37CGaJfyRVnICQGX.rrbJKqn1P7vvVqN.RtlAyiSe0ckNBiK', '2025'),
('u_700001aabbccdd.11111111', 'Pedro Costa', NULL, '1990-03-12', 'pedro.costa@email.com', '$2y$10$hashpedro', '2025'),
('u_700002bbccddee.22222222', 'Sofia Almeida', NULL, '1994-07-08', 'sofia.almeida@email.com', '$2y$10$hashsofia', '2025');

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_followers`
--

CREATE TABLE `user_followers` (
  `follower_id` varchar(100) NOT NULL,
  `following_id` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `user_followers`
--

INSERT INTO `user_followers` (`follower_id`, `following_id`) VALUES
('u_693eeca0932690.97050524', 'u_693eea932ce4e0.57322484'),
('u_700001aabbccdd.11111111', 'u_693eeca0932690.97050524'),
('u_700002bbccddee.22222222', 'u_693eeca0932690.97050524');

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_liked_collections`
--

CREATE TABLE `user_liked_collections` (
  `id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `liked_collection_id` varchar(100) NOT NULL,
  `last_updated` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `user_liked_collections`
--

INSERT INTO `user_liked_collections` (`id`, `user_id`, `liked_collection_id`, `last_updated`) VALUES
(1, 'u_693eeca0932690.97050524', 'col-693eec276b541', '2025-12-14 17:35:08'),
(2, 'u_693eeca0932690.97050524', 'col-693eeb2c71266', '2025-12-14 17:35:10'),
(3, 'u_693eeca0932690.97050524', 'col-693ee8636259f', '2025-12-14 17:35:49'),
(6, 'u_700001aabbccdd.11111111', 'col-693eed01aa06d', '2025-12-14 18:05:40'),
(7, 'u_700002bbccddee.22222222', 'col-700010bbb2222', '2025-12-14 18:05:40'),
(8, 'u_693eeca0932690.97050524', 'col-700010bbb2222', '2025-12-14 18:05:53'),
(9, 'u_693eeca0932690.97050524', 'col-693eed01aa06d', '2025-12-14 18:06:01');

-- --------------------------------------------------------

--
-- Estrutura da tabela `user_liked_items`
--

CREATE TABLE `user_liked_items` (
  `id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `liked_item_id` varchar(100) NOT NULL,
  `last_updated` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `user_liked_items`
--

INSERT INTO `user_liked_items` (`id`, `user_id`, `liked_item_id`, `last_updated`) VALUES
(1, 'u_693eeca0932690.97050524', 'item-693ef331a8110', '2025-12-14 17:37:38'),
(4, 'u_700001aabbccdd.11111111', 'item-700020aaa1111', '2025-12-14 18:05:40'),
(5, 'u_700002bbccddee.22222222', 'item-700020bbb2222', '2025-12-14 18:05:40');

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
  ADD PRIMARY KEY (`event_id`);

--
-- Índices para tabela `event_ratings`
--
ALTER TABLE `event_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_user_collection` (`event_id`,`user_id`,`collection_id`),
  ADD KEY `idx_event_ratings_collection` (`collection_id`),
  ADD KEY `fk_er_user` (`user_id`);

--
-- Índices para tabela `event_rsvps`
--
ALTER TABLE `event_rsvps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_rsvp` (`event_id`,`user_id`),
  ADD KEY `idx_rsvp_user` (`user_id`);

--
-- Índices para tabela `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`);

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
  ADD KEY `idx_following` (`following_id`);

--
-- Índices para tabela `user_liked_collections`
--
ALTER TABLE `user_liked_collections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_collection` (`user_id`,`liked_collection_id`),
  ADD KEY `idx_ulc_collection` (`liked_collection_id`);

--
-- Índices para tabela `user_liked_items`
--
ALTER TABLE `user_liked_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_item` (`user_id`,`liked_item_id`),
  ADD KEY `idx_uli_item` (`liked_item_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `event_ratings`
--
ALTER TABLE `event_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `event_rsvps`
--
ALTER TABLE `event_rsvps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `user_liked_collections`
--
ALTER TABLE `user_liked_collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `user_liked_items`
--
ALTER TABLE `user_liked_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Limitadores para a tabela `event_ratings`
--
ALTER TABLE `event_ratings`
  ADD CONSTRAINT `fk_er_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_er_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_er_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `event_rsvps`
--
ALTER TABLE `event_rsvps`
  ADD CONSTRAINT `fk_rsvp_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rsvp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `user_followers`
--
ALTER TABLE `user_followers`
  ADD CONSTRAINT `fk_uf_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uf_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `user_liked_collections`
--
ALTER TABLE `user_liked_collections`
  ADD CONSTRAINT `fk_ulc_collection` FOREIGN KEY (`liked_collection_id`) REFERENCES `collections` (`collection_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ulc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `user_liked_items`
--
ALTER TABLE `user_liked_items`
  ADD CONSTRAINT `fk_uli_item` FOREIGN KEY (`liked_item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uli_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
