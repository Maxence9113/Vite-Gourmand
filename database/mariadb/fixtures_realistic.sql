-- ============================================================================
-- FIXTURES RÉALISTES - Vite & Gourmand
-- ============================================================================
-- Ce fichier contient un jeu de données complet et réaliste pour l'application
-- Images : Unsplash (URLs directes)
--
-- IMPORTANT: Ce fichier est idempotent, il peut être exécuté plusieurs fois
-- Il vide et réinitialise toutes les tables avant d'insérer les données
-- ============================================================================

-- ============================================================================
-- ÉTAPE 1 : NETTOYAGE ET RÉINITIALISATION DE LA BASE
-- ============================================================================

-- Désactiver temporairement les contraintes de clés étrangères
-- pour permettre la suppression des données
SET FOREIGN_KEY_CHECKS = 0;

-- Vider toutes les tables (TRUNCATE = suppression rapide + reset auto-increment)
TRUNCATE TABLE password_reset_token;
TRUNCATE TABLE `order`;
TRUNCATE TABLE review;
TRUNCATE TABLE address;
TRUNCATE TABLE opening_schedule;
TRUNCATE TABLE menu_recipe;
TRUNCATE TABLE menu_dietetary;
TRUNCATE TABLE menu;
TRUNCATE TABLE recipe_illustration;
TRUNCATE TABLE recipe_allergen;
TRUNCATE TABLE recipe;
TRUNCATE TABLE theme;
TRUNCATE TABLE dietetary;
TRUNCATE TABLE category;
TRUNCATE TABLE allergen;
TRUNCATE TABLE `user`;

-- Réactiver les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- ÉTAPE 2 : INSERTION DES DONNÉES
-- ============================================================================

-- ============================================================================
-- 1. UTILISATEURS
-- ============================================================================

-- Administrateur José (ROLE_ADMIN)
-- Mot de passe: Admin1234!@
INSERT INTO user (email, firstname, lastname, roles, password, is_enabled)
VALUES (
    'jose@vitegourmand.fr',
    'José',
    'Martinez',
    '["ROLE_ADMIN"]',
    '$2y$13$8JWNJQ4tE6llhMYhqVYIy.r1PR/OP3KBydOWiVaAzV20xXvv69hDe',
    1
);

-- Employée Julie (ROLE_EMPLOYEE)
-- Mot de passe: Employee123!@
INSERT INTO user (email, firstname, lastname, roles, password, is_enabled)
VALUES (
    'julie@vitegourmand.fr',
    'Julie',
    'Dupont',
    '["ROLE_EMPLOYEE"]',
    '$2y$13$BuqIMZC.q9X5IMGuK6Bcee.qlEijMl5exeJM6yfyu.KNXCIznbWt6',
    1
);

-- Utilisateurs clients (ROLE_USER)
-- Mot de passe pour tous: User1234!@
INSERT INTO user (email, firstname, lastname, roles, password, is_enabled) VALUES
('sophie.martin@email.fr', 'Sophie', 'Martin', '["ROLE_USER"]', '$2y$13$LtX3pEAxZx2MzAOn80JzNeWl2gsmmy9QOcqmA2hECM7IrcrIPiZza', 1),
('lucas.bernard@email.fr', 'Lucas', 'Bernard', '["ROLE_USER"]', '$2y$13$LtX3pEAxZx2MzAOn80JzNeWl2gsmmy9QOcqmA2hECM7IrcrIPiZza', 1),
('marie.dubois@email.fr', 'Marie', 'Dubois', '["ROLE_USER"]', '$2y$13$LtX3pEAxZx2MzAOn80JzNeWl2gsmmy9QOcqmA2hECM7IrcrIPiZza', 1),
('thomas.laurent@email.fr', 'Thomas', 'Laurent', '["ROLE_USER"]', '$2y$13$LtX3pEAxZx2MzAOn80JzNeWl2gsmmy9QOcqmA2hECM7IrcrIPiZza', 1),
('emma.petit@email.fr', 'Emma', 'Petit', '["ROLE_USER"]', '$2y$13$LtX3pEAxZx2MzAOn80JzNeWl2gsmmy9QOcqmA2hECM7IrcrIPiZza', 1),
('hugo.robert@email.fr', 'Hugo', 'Robert', '["ROLE_USER"]', '$2y$13$LtX3pEAxZx2MzAOn80JzNeWl2gsmmy9QOcqmA2hECM7IrcrIPiZza', 1),
('lea.moreau@email.fr', 'Léa', 'Moreau', '["ROLE_USER"]', '$2y$13$LtX3pEAxZx2MzAOn80JzNeWl2gsmmy9QOcqmA2hECM7IrcrIPiZza', 1),
('arthur.simon@email.fr', 'Arthur', 'Simon', '["ROLE_USER"]', '$2y$13$LtX3pEAxZx2MzAOn80JzNeWl2gsmmy9QOcqmA2hECM7IrcrIPiZza', 1),
('chloe.michel@email.fr', 'Chloé', 'Michel', '["ROLE_USER"]', '$2y$13$LtX3pEAxZx2MzAOn80JzNeWl2gsmmy9QOcqmA2hECM7IrcrIPiZza', 1),
('nathan.lefebvre@email.fr', 'Nathan', 'Lefebvre', '["ROLE_USER"]', '$2y$13$LtX3pEAxZx2MzAOn80JzNeWl2gsmmy9QOcqmA2hECM7IrcrIPiZza', 1);

-- ============================================================================
-- 2. ADRESSES DES UTILISATEURS
-- ============================================================================
-- Note: user_id = 1 (José), 2 (Julie), 3 (Sophie), 4 (Lucas), etc.

-- Adresses pour Sophie Martin (user_id = 3)
INSERT INTO address (label, street, city, postal_code, phone, is_default, user_id) VALUES
('Domicile', '15 rue des Lilas', 'Paris', '75015', '0612345678', 1, 3),
('Travail', '42 avenue Victor Hugo', 'Paris', '75016', '0612345678', 0, 3);

-- Adresses pour Lucas Bernard (user_id = 4)
INSERT INTO address (label, street, city, postal_code, phone, is_default, user_id) VALUES
('Maison', '28 rue de la République', 'Lyon', '69002', '0623456789', 1, 4);

-- Adresses pour Marie Dubois (user_id = 5)
INSERT INTO address (label, street, city, postal_code, phone, is_default, user_id) VALUES
('Domicile', '7 boulevard des Capucines', 'Marseille', '13001', '0634567890', 1, 5);

-- Adresses pour Thomas Laurent (user_id = 6)
INSERT INTO address (label, street, city, postal_code, phone, is_default, user_id) VALUES
('Maison', '35 rue du Commerce', 'Toulouse', '31000', '0645678901', 1, 6),
('Parents', '12 allée des Roses', 'Toulouse', '31100', '0645678901', 0, 6);

-- Adresses pour Emma Petit (user_id = 7)
INSERT INTO address (label, street, city, postal_code, phone, is_default, user_id) VALUES
('Appartement', '18 rue Saint-Jacques', 'Nice', '06000', '0656789012', 1, 7);

-- Adresses pour Hugo Robert (user_id = 8)
INSERT INTO address (label, street, city, postal_code, phone, is_default, user_id) VALUES
('Domicile', '9 place de la Comédie', 'Bordeaux', '33000', '0667890123', 1, 8);

-- Adresses pour Léa Moreau (user_id = 9)
INSERT INTO address (label, street, city, postal_code, phone, is_default, user_id) VALUES
('Maison', '22 avenue Jean Jaurès', 'Nantes', '44000', '0678901234', 1, 9);

-- Adresses pour Arthur Simon (user_id = 10)
INSERT INTO address (label, street, city, postal_code, phone, is_default, user_id) VALUES
('Appartement', '5 rue de la Liberté', 'Strasbourg', '67000', '0689012345', 1, 10);

-- Adresses pour Chloé Michel (user_id = 11)
INSERT INTO address (label, street, city, postal_code, phone, is_default, user_id) VALUES
('Domicile', '31 cours Mirabeau', 'Aix-en-Provence', '13100', '0690123456', 1, 11);

-- Adresses pour Nathan Lefebvre (user_id = 12)
INSERT INTO address (label, street, city, postal_code, phone, is_default, user_id) VALUES
('Maison', '14 rue de Belleville', 'Paris', '75020', '0601234567', 1, 12);

-- ============================================================================
-- 3. ALLERGÈNES
-- ============================================================================

INSERT INTO allergen (name) VALUES
('Gluten'),
('Crustacés'),
('Œufs'),
('Poissons'),
('Arachides'),
('Soja'),
('Lait'),
('Fruits à coque'),
('Céleri'),
('Moutarde'),
('Graines de sésame'),
('Sulfites'),
('Lupin'),
('Mollusques');

-- ============================================================================
-- 4. RÉGIMES DIÉTÉTIQUES
-- ============================================================================

INSERT INTO dietetary (name) VALUES
('Végétarien'),
('Vegan'),
('Sans gluten'),
('Halal'),
('Casher');

-- ============================================================================
-- 5. CATÉGORIES DE RECETTES
-- ============================================================================

INSERT INTO category (name) VALUES
('Entrée'),
('Plat'),
('Fromage'),
('Dessert');

-- ============================================================================
-- 6. THÈMES
-- ============================================================================

INSERT INTO theme (name, description) VALUES
('Noël', 'Un menu festif pour célébrer les fêtes de fin d''année en famille, avec des plats traditionnels et chaleureux, mêlant saveurs authentiques et touches modernes.'),
('Réveillon du Nouvel An', 'Un menu élégant et raffiné pour célébrer le passage à la nouvelle année, avec des mets d''exception et des saveurs sophistiquées pour une soirée mémorable.'),
('Anniversaire', 'Un menu personnalisable et convivial pour célébrer un anniversaire de manière mémorable, adapté à tous les âges et toutes les envies festives.'),
('Mariage', 'Un menu élégant et gastronomique pour célébrer l''union de deux personnes, composé de mets raffinés et de présentations soignées pour un jour unique.'),
('Barbecue estival', 'Un menu convivial pour profiter des beaux jours en extérieur, avec des grillades savoureuses, des salades fraîches et des accompagnements généreux.'),
('Pâques', 'Un menu printanier célébrant le renouveau et la fraîcheur de saison, avec des saveurs délicates, des légumes primeurs et des desserts gourmands.');

-- ============================================================================
-- 7. RECETTES
-- ============================================================================

-- Entrées (catégorie 1)
INSERT INTO recipe (title, description, category_id) VALUES
('Velouté de châtaignes', 'Velouté onctueux de châtaignes avec crème de marrons et copeaux de foie gras', 1),
('Saumon gravlax maison', 'Saumon mariné aux agrumes et aneth, servi avec blinis et crème acidulée', 1),
('Terrine de foie gras', 'Terrine de foie gras mi-cuit accompagnée de chutney de figues et pain toasté', 1),
('Salade de homard', 'Salade fraîche avec morceaux de homard, avocat et vinaigrette aux agrumes', 1),
('Carpaccio de Saint-Jacques', 'Saint-Jacques marinées au citron vert, huile d''olive et fleur de sel', 1),
('Asperges vertes rôties', 'Asperges rôties au four, parmesan et vinaigrette balsamique', 1),
('Gaspacho de tomates', 'Soupe froide espagnole à base de tomates mûres, concombre et poivron', 1),
('Feuilleté aux morilles', 'Feuilleté croustillant garni de morilles et crème à la truffe', 1),
('Cassolette d''escargots', 'Escargots de Bourgogne au beurre persillé en cassolette individuelle', 1),
('Soufflé au fromage', 'Soufflé léger et aérien au comté et gruyère, cuit à la perfection', 1);

-- Plats principaux (catégorie 2)
INSERT INTO recipe (title, description, category_id) VALUES
('Dinde farcie aux marrons', 'Dinde rôtie traditionnelle farcie aux marrons et herbes aromatiques', 2),
('Chapon rôti sauce suprême', 'Chapon fermier rôti accompagné d''une sauce suprême crémeuse', 2),
('Filet de bœuf Wellington', 'Filet de bœuf en croûte de pâte feuilletée avec duxelles de champignons', 2),
('Pavé de saumon grillé', 'Pavé de saumon grillé avec beurre blanc et légumes de saison', 2),
('Magret de canard aux fruits', 'Magret de canard rôti accompagné d''une sauce aux fruits rouges', 2),
('Gigot d''agneau rôti', 'Gigot d''agneau rôti au four avec herbes de Provence et ail confit', 2),
('Bar en croûte de sel', 'Bar entier cuit en croûte de sel, chair moelleuse et parfumée', 2),
('Côte de bœuf grillée', 'Côte de bœuf grillée à la perfection, servie saignante ou à point', 2),
('Risotto aux cèpes', 'Risotto crémeux aux cèpes frais et parmesan, sans produits animaux pour version vegan', 2),
('Pavé de cabillaud rôti', 'Cabillaud rôti avec croûte d''herbes et légumes méditerranéens', 2),
('Gratin dauphinois', 'Gratin de pommes de terre crémeux à la crème fraîche et ail', 2),
('Purée de pommes de terre', 'Purée maison onctueuse au beurre et lait entier', 2),
('Haricots verts amandine', 'Haricots verts sautés au beurre avec amandes effilées grillées', 2),
('Poêlée de légumes', 'Mélange de légumes de saison poêlés au beurre et herbes fraîches', 2),
('Pommes de terre sarladaises', 'Pommes de terre sautées à la graisse de canard et persil', 2),
('Riz pilaf', 'Riz basmati cuit au bouillon avec oignons dorés', 2),
('Légumes grillés', 'Assortiment de légumes de saison grillés au four avec huile d''olive', 2);

-- Fromages (catégorie 3)
INSERT INTO recipe (title, description, category_id) VALUES
('Plateau de fromages affinés', 'Sélection de 5 fromages affinés français accompagnés de confiture', 3);

-- Desserts (catégorie 4)
INSERT INTO recipe (title, description, category_id) VALUES
('Bûche de Noël chocolat', 'Bûche traditionnelle au chocolat avec crème au beurre et décor festif', 4),
('Tarte Tatin maison', 'Tarte aux pommes caramélisées servie tiède avec boule de glace vanille', 4),
('Profiteroles au chocolat', 'Choux garnis de glace vanille nappés de sauce chocolat chaude', 4),
('Fondant au chocolat', 'Gâteau au chocolat coulant servi avec crème anglaise', 4),
('Crème brûlée vanille', 'Crème onctueuse à la vanille avec croûte de sucre caramélisé', 4),
('Tiramisu traditionnel', 'Dessert italien aux biscuits imbibés de café et mascarpone', 4),
('Millefeuille vanille', 'Pâte feuilletée croustillante avec crème pâtissière vanille', 4),
('Mousse au chocolat', 'Mousse légère et aérienne au chocolat noir 70%', 4),
('Panna cotta fruits rouges', 'Crème italienne vanillée avec coulis de fruits rouges', 4),
('Tarte au citron meringuée', 'Tarte avec crème au citron acidulée et meringue italienne dorée', 4);

-- ============================================================================
-- 8. RELATIONS RECETTES-ALLERGÈNES
-- ============================================================================

-- Velouté de châtaignes (id=1) - Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (1, 7);

-- Saumon gravlax (id=2) - Poissons, Gluten
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (2, 4), (2, 1);

-- Terrine de foie gras (id=3) - Gluten, Sulfites
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (3, 1), (3, 12);

-- Salade de homard (id=4) - Crustacés
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (4, 2);

-- Carpaccio de Saint-Jacques (id=5) - Mollusques
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (5, 14);

-- Asperges vertes rôties (id=6) - Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (6, 7);

-- Feuilleté aux morilles (id=8) - Gluten, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (8, 1), (8, 7);

-- Cassolette d'escargots (id=9) - Lait, Mollusques
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (9, 7), (9, 14);

-- Soufflé au fromage (id=10) - Gluten, Œufs, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (10, 1), (10, 3), (10, 7);

-- Dinde farcie (id=11) - Gluten, Céleri
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (11, 1), (11, 9);

-- Filet de bœuf Wellington (id=13) - Gluten, Œufs
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (13, 1), (13, 3);

-- Pavé de saumon (id=14) - Poissons
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (14, 4);

-- Bar en croûte de sel (id=17) - Poissons
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (17, 4);

-- Pavé de cabillaud (id=20) - Poissons
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (20, 4);

-- Gratin dauphinois (id=21) - Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (21, 7);

-- Purée de pommes de terre (id=22) - Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (22, 7);

-- Haricots verts amandine (id=23) - Fruits à coque, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (23, 8), (23, 7);

-- Plateau de fromages (id=28) - Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (28, 7);

-- Bûche de Noël (id=29) - Gluten, Œufs, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (29, 1), (29, 3), (29, 7);

-- Tarte Tatin (id=30) - Gluten, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (30, 1), (30, 7);

-- Profiteroles (id=31) - Gluten, Œufs, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (31, 1), (31, 3), (31, 7);

-- Fondant au chocolat (id=32) - Gluten, Œufs, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (32, 1), (32, 3), (32, 7);

-- Crème brûlée (id=33) - Œufs, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (33, 3), (33, 7);

-- Tiramisu (id=34) - Gluten, Œufs, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (34, 1), (34, 3), (34, 7);

-- Millefeuille (id=35) - Gluten, Œufs, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (35, 1), (35, 3), (35, 7);

-- Mousse au chocolat (id=36) - Œufs, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (36, 3), (36, 7);

-- Panna cotta (id=37) - Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (37, 7);

-- Tarte au citron (id=38) - Gluten, Œufs, Lait
INSERT INTO recipe_allergen (recipe_id, allergen_id) VALUES (38, 1), (38, 3), (38, 7);

-- ============================================================================
-- 9. ILLUSTRATIONS DES RECETTES
-- ============================================================================

-- Images Unsplash pour les recettes principales
INSERT INTO recipe_illustration (name, url, alt_text, recipe_id) VALUES
('Velouté de châtaignes', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800', 'Velouté de châtaignes servi dans un bol blanc', 1),
('Saumon gravlax', 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=800', 'Tranches de saumon gravlax mariné', 2),
('Terrine de foie gras', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=800', 'Terrine de foie gras avec chutney', 3),
('Dinde farcie', 'https://images.unsplash.com/photo-1574672280600-4accfa5b6f98?w=800', 'Dinde rôtie farcie aux marrons', 11),
('Chapon rôti', 'https://images.unsplash.com/photo-1629998267715-bcfdd8e9ff77?w=800', 'Chapon rôti doré au four', 12),
('Filet de bœuf Wellington', 'https://images.unsplash.com/photo-1558030006-450675393462?w=800', 'Filet de bœuf Wellington en croûte', 13),
('Pavé de saumon', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800', 'Pavé de saumon grillé avec légumes', 14),
('Gratin dauphinois', 'https://images.unsplash.com/photo-1600289031464-74d374b64991?w=800', 'Gratin dauphinois doré', 21),
('Bûche de Noël', 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=800', 'Bûche de Noël au chocolat décorée', 29),
('Tarte Tatin', 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=800', 'Tarte Tatin aux pommes caramélisées', 30),
('Profiteroles', 'https://images.unsplash.com/photo-1571115177098-24ec42ed204d?w=800', 'Profiteroles nappées de chocolat', 31),
('Fondant au chocolat', 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=800', 'Fondant au chocolat coulant', 32);

-- ============================================================================
-- 10. MENUS
-- ============================================================================

-- MENU 1: Menu de Noël Traditionnel
-- Thème Noël (id=1), Prix 65€/personne, Min 8 personnes
INSERT INTO menu (name, nb_person_min, price_per_person, description, illustration, text_alt, stock, theme_id)
VALUES (
    'Menu de Noël Traditionnel',
    8,
    6500,
    'Un menu festif qui célèbre les traditions de Noël avec des saveurs authentiques et généreuses. De l''entrée au dessert, chaque plat évoque la magie des fêtes de fin d''année.',
    'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=1200',
    'Table de Noël dressée avec décoration festive',
    NULL,
    1
);

-- MENU 2: Menu Réveillon Prestige
-- Thème Réveillon (id=2), Prix 95€/personne, Min 10 personnes
INSERT INTO menu (name, nb_person_min, price_per_person, description, illustration, text_alt, stock, theme_id)
VALUES (
    'Menu Réveillon Prestige',
    10,
    9500,
    'Un menu d''exception pour célébrer le passage à la nouvelle année avec élégance et raffinement. Des mets prestigieux pour une soirée inoubliable.',
    'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=1200',
    'Table élégante dressée pour le réveillon',
    NULL,
    2
);

-- MENU 3: Menu Anniversaire Convivial
-- Thème Anniversaire (id=3), Prix 45€/personne, Min 10 personnes, Végétarien
INSERT INTO menu (name, nb_person_min, price_per_person, description, illustration, text_alt, stock, theme_id)
VALUES (
    'Menu Anniversaire Convivial',
    10,
    4500,
    'Un menu festif et adaptable pour célébrer un anniversaire dans la joie et la bonne humeur. Des plats qui plaisent à tous les âges.',
    'https://images.unsplash.com/photo-1558636508-e0db3814bd1d?w=1200',
    'Table d''anniversaire avec décoration colorée',
    NULL,
    3
);

-- MENU 4: Menu Mariage Élégance
-- Thème Mariage (id=4), Prix 85€/personne, Min 30 personnes
INSERT INTO menu (name, nb_person_min, price_per_person, description, illustration, text_alt, stock, theme_id)
VALUES (
    'Menu Mariage Élégance',
    30,
    8500,
    'Un menu raffiné et gastronomique pour célébrer votre union. Chaque plat est préparé avec soin pour rendre ce jour unique et mémorable.',
    'https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=1200',
    'Table de mariage élégante avec décoration florale',
    NULL,
    4
);

-- MENU 5: Menu Barbecue d'Été
-- Thème Barbecue estival (id=5), Prix 38€/personne, Min 15 personnes
INSERT INTO menu (name, nb_person_min, price_per_person, description, illustration, text_alt, stock, theme_id)
VALUES (
    'Menu Barbecue d''Été',
    15,
    3800,
    'Un menu convivial pour profiter des beaux jours en extérieur. Grillades savoureuses, salades fraîches et ambiance estivale garantie.',
    'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=1200',
    'Barbecue en extérieur avec grillades',
    NULL,
    5
);

-- MENU 6: Menu de Pâques Gourmand
-- Thème Pâques (id=6), Prix 52€/personne, Min 8 personnes
INSERT INTO menu (name, nb_person_min, price_per_person, description, illustration, text_alt, stock, theme_id)
VALUES (
    'Menu de Pâques Gourmand',
    8,
    5200,
    'Un menu printanier qui célèbre le renouveau avec des saveurs fraîches et délicates. Légumes de saison et agneau traditionnel à l''honneur.',
    'https://images.unsplash.com/photo-1490818387583-1baba5e638af?w=1200',
    'Table de Pâques avec décoration printanière',
    NULL,
    6
);

-- MENU 7: Menu Végétarien Raffiné
-- Thème Anniversaire (id=3), Prix 48€/personne, Min 10 personnes, Végétarien
INSERT INTO menu (name, nb_person_min, price_per_person, description, illustration, text_alt, stock, theme_id)
VALUES (
    'Menu Végétarien Raffiné',
    10,
    4800,
    'Un menu 100% végétarien sans compromis sur le goût et l''élégance. Des légumes de saison sublimés par nos chefs pour un voyage culinaire respectueux.',
    'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=1200',
    'Assiette végétarienne raffinée et colorée',
    NULL,
    3
);

-- MENU 8: Menu Fruits de Mer
-- Thème Réveillon (id=2), Prix 78€/personne, Min 12 personnes
INSERT INTO menu (name, nb_person_min, price_per_person, description, illustration, text_alt, stock, theme_id)
VALUES (
    'Menu Fruits de Mer',
    12,
    7800,
    'Un menu dédié aux amateurs de produits de la mer. Fraîcheur et qualité sont au rendez-vous pour ce festin marin d''exception.',
    'https://images.unsplash.com/photo-1615141982883-c7ad0e69fd62?w=1200',
    'Plateau de fruits de mer frais',
    NULL,
    2
);

-- MENU 9: Menu Découverte
-- Thème Anniversaire (id=3), Prix 42€/personne, Min 8 personnes
INSERT INTO menu (name, nb_person_min, price_per_person, description, illustration, text_alt, stock, theme_id)
VALUES (
    'Menu Découverte',
    8,
    4200,
    'Un menu accessible qui permet de découvrir notre savoir-faire à travers une sélection de nos meilleures recettes. Parfait pour une première expérience.',
    'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200',
    'Table dressée avec plusieurs plats variés',
    NULL,
    3
);

-- MENU 10: Menu Noël Vegan
-- Thème Noël (id=1), Prix 58€/personne, Min 8 personnes, Vegan
INSERT INTO menu (name, nb_person_min, price_per_person, description, illustration, text_alt, stock, theme_id)
VALUES (
    'Menu de Noël Vegan',
    8,
    5800,
    'Un menu de Noël 100% végétal qui ne fait aucune concession sur la gourmandise. Découvrez comment célébrer les fêtes tout en respectant vos valeurs.',
    'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=1200',
    'Plats végétaliens de fêtes',
    NULL,
    1
);

-- ============================================================================
-- 11. RELATIONS MENUS-RÉGIMES DIÉTÉTIQUES
-- ============================================================================

-- Menu Anniversaire Convivial (id=3) - Végétarien
INSERT INTO menu_dietetary (menu_id, dietetary_id) VALUES (3, 1);

-- Menu Végétarien Raffiné (id=7) - Végétarien
INSERT INTO menu_dietetary (menu_id, dietetary_id) VALUES (7, 1);

-- Menu Noël Vegan (id=10) - Vegan
INSERT INTO menu_dietetary (menu_id, dietetary_id) VALUES (10, 2);

-- Menu Mariage Élégance (id=4) - Halal (option disponible)
INSERT INTO menu_dietetary (menu_id, dietetary_id) VALUES (4, 4);

-- ============================================================================
-- 12. RELATIONS MENUS-RECETTES
-- ============================================================================

-- MENU 1: Menu de Noël Traditionnel
INSERT INTO menu_recipe (menu_id, recipe_id) VALUES
(1, 1),   -- Velouté de châtaignes
(1, 11),  -- Dinde farcie aux marrons
(1, 21),  -- Gratin dauphinois
(1, 23),  -- Haricots verts amandine
(1, 28),  -- Plateau de fromages
(1, 29);  -- Bûche de Noël

-- MENU 2: Menu Réveillon Prestige
INSERT INTO menu_recipe (menu_id, recipe_id) VALUES
(2, 3),   -- Terrine de foie gras
(2, 4),   -- Salade de homard
(2, 13),  -- Filet de bœuf Wellington
(2, 21),  -- Gratin dauphinois
(2, 28),  -- Plateau de fromages
(2, 31);  -- Profiteroles au chocolat

-- MENU 3: Menu Anniversaire Convivial
INSERT INTO menu_recipe (menu_id, recipe_id) VALUES
(3, 6),   -- Asperges vertes rôties
(3, 19),  -- Risotto aux cèpes
(3, 24),  -- Poêlée de légumes
(3, 30);  -- Tarte Tatin

-- MENU 4: Menu Mariage Élégance
INSERT INTO menu_recipe (menu_id, recipe_id) VALUES
(4, 2),   -- Saumon gravlax
(4, 8),   -- Feuilleté aux morilles
(4, 15),  -- Magret de canard aux fruits
(4, 21),  -- Gratin dauphinois
(4, 28),  -- Plateau de fromages
(4, 35);  -- Millefeuille vanille

-- MENU 5: Menu Barbecue d'Été
INSERT INTO menu_recipe (menu_id, recipe_id) VALUES
(5, 7),   -- Gaspacho de tomates
(5, 18),  -- Côte de bœuf grillée
(5, 27),  -- Légumes grillés
(5, 37);  -- Panna cotta fruits rouges

-- MENU 6: Menu de Pâques Gourmand
INSERT INTO menu_recipe (menu_id, recipe_id) VALUES
(6, 6),   -- Asperges vertes rôties
(6, 16),  -- Gigot d'agneau rôti
(6, 22),  -- Purée de pommes de terre
(6, 24),  -- Poêlée de légumes
(6, 38);  -- Tarte au citron meringuée

-- MENU 7: Menu Végétarien Raffiné
INSERT INTO menu_recipe (menu_id, recipe_id) VALUES
(7, 6),   -- Asperges vertes rôties
(7, 10),  -- Soufflé au fromage
(7, 19),  -- Risotto aux cèpes
(7, 27),  -- Légumes grillés
(7, 36);  -- Mousse au chocolat

-- MENU 8: Menu Fruits de Mer
INSERT INTO menu_recipe (menu_id, recipe_id) VALUES
(8, 5),   -- Carpaccio de Saint-Jacques
(8, 4),   -- Salade de homard
(8, 17),  -- Bar en croûte de sel
(8, 26),  -- Riz pilaf
(8, 33);  -- Crème brûlée vanille

-- MENU 9: Menu Découverte
INSERT INTO menu_recipe (menu_id, recipe_id) VALUES
(9, 2),   -- Saumon gravlax
(9, 14),  -- Pavé de saumon grillé
(9, 24),  -- Poêlée de légumes
(9, 32);  -- Fondant au chocolat

-- MENU 10: Menu Noël Vegan
INSERT INTO menu_recipe (menu_id, recipe_id) VALUES
(10, 7),  -- Gaspacho de tomates
(10, 19), -- Risotto aux cèpes (version vegan)
(10, 27), -- Légumes grillés
(10, 37); -- Panna cotta (version vegan avec lait végétal)

-- ============================================================================
-- 13. HORAIRES D'OUVERTURE
-- ============================================================================

INSERT INTO opening_schedule (day_of_week, opening_time, closing_time, is_open, created_at, updated_at) VALUES
(1, '09:00:00', '18:00:00', 1, NOW(), NOW()), -- Lundi: 9h-18h
(2, '09:00:00', '18:00:00', 1, NOW(), NOW()), -- Mardi: 9h-18h
(3, '09:00:00', '18:00:00', 1, NOW(), NOW()), -- Mercredi: 9h-18h
(4, '09:00:00', '18:00:00', 1, NOW(), NOW()), -- Jeudi: 9h-18h
(5, '09:00:00', '18:00:00', 1, NOW(), NOW()), -- Vendredi: 9h-18h
(6, '09:00:00', '13:00:00', 1, NOW(), NOW()), -- Samedi: 9h-13h
(7, NULL, NULL, 0, NOW(), NOW());          -- Dimanche: Fermé

-- ============================================================================
-- 14. COMMANDES
-- ============================================================================

-- Commande 1: Sophie Martin - Menu Noël (statut: completed)
INSERT INTO `order` (
    order_number, customer_firstname, customer_lastname, customer_email, customer_phone,
    delivery_address, delivery_date_time, delivery_distance_km, delivery_cost,
    menu_name, menu_price_per_person, number_of_persons, menu_subtotal,
    discount_amount, total_price, status, status_history,
    has_material_loan, material_return_deadline, material_returned,
    cancellation_reason, cancelled_at, created_at, updated_at, accepted_at, completed_at,
    user_id, review_id
) VALUES (
    'CMD-2024-001',
    'Sophie', 'Martin', 'sophie.martin@email.fr', '0612345678',
    '15 rue des Lilas, 75015 Paris',
    '2024-12-24 19:00:00',
    12, 1500,
    'Menu de Noël Traditionnel', 6500, 12, 78000,
    NULL, 79500, 'completed',
    '{"pending": "2024-11-15 10:30:00", "accepted": "2024-11-15 14:00:00", "completed": "2024-12-24 22:00:00"}',
    1, '2024-12-26 18:00:00', 1,
    NULL, NULL,
    '2024-11-15 10:30:00', '2024-12-24 22:00:00', '2024-11-15 14:00:00', '2024-12-24 22:00:00',
    3, NULL
);

-- Commande 2: Lucas Bernard - Menu Réveillon (statut: accepted)
INSERT INTO `order` (
    order_number, customer_firstname, customer_lastname, customer_email, customer_phone,
    delivery_address, delivery_date_time, delivery_distance_km, delivery_cost,
    menu_name, menu_price_per_person, number_of_persons, menu_subtotal,
    discount_amount, total_price, status, status_history,
    has_material_loan, material_return_deadline, material_returned,
    cancellation_reason, cancelled_at, created_at, updated_at, accepted_at, completed_at,
    user_id, review_id
) VALUES (
    'CMD-2024-002',
    'Lucas', 'Bernard', 'lucas.bernard@email.fr', '0623456789',
    '28 rue de la République, 69002 Lyon',
    '2024-12-31 20:00:00',
    NULL, 0,
    'Menu Réveillon Prestige', 9500, 15, 142500,
    NULL, 142500, 'accepted',
    '{"pending": "2024-11-20 09:15:00", "accepted": "2024-11-20 16:30:00"}',
    1, '2025-01-02 18:00:00', 0,
    NULL, NULL,
    '2024-11-20 09:15:00', '2024-11-20 16:30:00', '2024-11-20 16:30:00', NULL,
    4, NULL
);

-- Commande 3: Marie Dubois - Menu Anniversaire (statut: completed)
INSERT INTO `order` (
    order_number, customer_firstname, customer_lastname, customer_email, customer_phone,
    delivery_address, delivery_date_time, delivery_distance_km, delivery_cost,
    menu_name, menu_price_per_person, number_of_persons, menu_subtotal,
    discount_amount, total_price, status, status_history,
    has_material_loan, material_return_deadline, material_returned,
    cancellation_reason, cancelled_at, created_at, updated_at, accepted_at, completed_at,
    user_id, review_id
) VALUES (
    'CMD-2024-003',
    'Marie', 'Dubois', 'marie.dubois@email.fr', '0634567890',
    '7 boulevard des Capucines, 13001 Marseille',
    '2024-10-15 19:30:00',
    NULL, 0,
    'Menu Anniversaire Convivial', 4500, 20, 90000,
    4500, 85500, 'completed',
    '{"pending": "2024-09-20 11:00:00", "accepted": "2024-09-20 15:00:00", "completed": "2024-10-15 23:00:00"}',
    0, NULL, NULL,
    NULL, NULL,
    '2024-09-20 11:00:00', '2024-10-15 23:00:00', '2024-09-20 15:00:00', '2024-10-15 23:00:00',
    5, NULL
);

-- Commande 4: Thomas Laurent - Menu Mariage (statut: pending)
INSERT INTO `order` (
    order_number, customer_firstname, customer_lastname, customer_email, customer_phone,
    delivery_address, delivery_date_time, delivery_distance_km, delivery_cost,
    menu_name, menu_price_per_person, number_of_persons, menu_subtotal,
    discount_amount, total_price, status, status_history,
    has_material_loan, material_return_deadline, material_returned,
    cancellation_reason, cancelled_at, created_at, updated_at, accepted_at, completed_at,
    user_id, review_id
) VALUES (
    'CMD-2025-004',
    'Thomas', 'Laurent', 'thomas.laurent@email.fr', '0645678901',
    '35 rue du Commerce, 31000 Toulouse',
    '2025-06-14 19:00:00',
    NULL, 0,
    'Menu Mariage Élégance', 8500, 80, 680000,
    NULL, 680000, 'pending',
    '{"pending": "2024-12-05 14:20:00"}',
    1, '2025-06-16 18:00:00', 0,
    NULL, NULL,
    '2024-12-05 14:20:00', '2024-12-05 14:20:00', NULL, NULL,
    6, NULL
);

-- Commande 5: Emma Petit - Menu Barbecue (statut: completed)
INSERT INTO `order` (
    order_number, customer_firstname, customer_lastname, customer_email, customer_phone,
    delivery_address, delivery_date_time, delivery_distance_km, delivery_cost,
    menu_name, menu_price_per_person, number_of_persons, menu_subtotal,
    discount_amount, total_price, status, status_history,
    has_material_loan, material_return_deadline, material_returned,
    cancellation_reason, cancelled_at, created_at, updated_at, accepted_at, completed_at,
    user_id, review_id
) VALUES (
    'CMD-2024-005',
    'Emma', 'Petit', 'emma.petit@email.fr', '0656789012',
    '18 rue Saint-Jacques, 06000 Nice',
    '2024-07-20 12:00:00',
    8, 1000,
    'Menu Barbecue d''Été', 3800, 25, 95000,
    NULL, 96000, 'completed',
    '{"pending": "2024-06-10 10:00:00", "accepted": "2024-06-10 16:00:00", "completed": "2024-07-20 16:00:00"}',
    1, '2024-07-22 18:00:00', 1,
    NULL, NULL,
    '2024-06-10 10:00:00', '2024-07-20 16:00:00', '2024-06-10 16:00:00', '2024-07-20 16:00:00',
    7, NULL
);

-- Commande 6: Hugo Robert - Menu Fruits de Mer (statut: cancelled)
INSERT INTO `order` (
    order_number, customer_firstname, customer_lastname, customer_email, customer_phone,
    delivery_address, delivery_date_time, delivery_distance_km, delivery_cost,
    menu_name, menu_price_per_person, number_of_persons, menu_subtotal,
    discount_amount, total_price, status, status_history,
    has_material_loan, material_return_deadline, material_returned,
    cancellation_reason, cancelled_at, created_at, updated_at, accepted_at, completed_at,
    user_id, review_id
) VALUES (
    'CMD-2024-006',
    'Hugo', 'Robert', 'hugo.robert@email.fr', '0667890123',
    '9 place de la Comédie, 33000 Bordeaux',
    '2024-11-10 19:30:00',
    NULL, 0,
    'Menu Fruits de Mer', 7800, 18, 140400,
    NULL, 140400, 'cancelled',
    '{"pending": "2024-10-15 09:30:00", "cancelled": "2024-10-20 11:00:00"}',
    0, NULL, NULL,
    'Le client a annulé en raison d''un changement de date d''événement',
    '2024-10-20 11:00:00',
    '2024-10-15 09:30:00', '2024-10-20 11:00:00', NULL, NULL,
    8, NULL
);

-- Commande 7: Léa Moreau - Menu Pâques (statut: completed)
INSERT INTO `order` (
    order_number, customer_firstname, customer_lastname, customer_email, customer_phone,
    delivery_address, delivery_date_time, delivery_distance_km, delivery_cost,
    menu_name, menu_price_per_person, number_of_persons, menu_subtotal,
    discount_amount, total_price, status, status_history,
    has_material_loan, material_return_deadline, material_returned,
    cancellation_reason, cancelled_at, created_at, updated_at, accepted_at, completed_at,
    user_id, review_id
) VALUES (
    'CMD-2024-007',
    'Léa', 'Moreau', 'lea.moreau@email.fr', '0678901234',
    '22 avenue Jean Jaurès, 44000 Nantes',
    '2024-04-01 12:30:00',
    15, 1800,
    'Menu de Pâques Gourmand', 5200, 10, 52000,
    NULL, 53800, 'completed',
    '{"pending": "2024-03-01 14:00:00", "accepted": "2024-03-02 10:00:00", "completed": "2024-04-01 16:00:00"}',
    1, '2024-04-03 18:00:00', 1,
    NULL, NULL,
    '2024-03-01 14:00:00', '2024-04-01 16:00:00', '2024-03-02 10:00:00', '2024-04-01 16:00:00',
    9, NULL
);

-- Commande 8: Arthur Simon - Menu Végétarien (statut: completed)
INSERT INTO `order` (
    order_number, customer_firstname, customer_lastname, customer_email, customer_phone,
    delivery_address, delivery_date_time, delivery_distance_km, delivery_cost,
    menu_name, menu_price_per_person, number_of_persons, menu_subtotal,
    discount_amount, total_price, status, status_history,
    has_material_loan, material_return_deadline, material_returned,
    cancellation_reason, cancelled_at, created_at, updated_at, accepted_at, completed_at,
    user_id, review_id
) VALUES (
    'CMD-2024-008',
    'Arthur', 'Simon', 'arthur.simon@email.fr', '0689012345',
    '5 rue de la Liberté, 67000 Strasbourg',
    '2024-09-15 19:00:00',
    NULL, 0,
    'Menu Végétarien Raffiné', 4800, 15, 72000,
    3600, 68400, 'completed',
    '{"pending": "2024-08-20 11:30:00", "accepted": "2024-08-20 17:00:00", "completed": "2024-09-15 22:30:00"}',
    0, NULL, NULL,
    NULL, NULL,
    '2024-08-20 11:30:00', '2024-09-15 22:30:00', '2024-08-20 17:00:00', '2024-09-15 22:30:00',
    10, NULL
);

-- ============================================================================
-- 15. AVIS CLIENTS
-- ============================================================================
-- Note: On insère d'abord les avis SANS order_ref_id pour éviter les problèmes
-- de dépendance circulaire (order -> review et review -> order)

-- Avis pour la commande 1 (Sophie Martin - Menu Noël)
INSERT INTO review (customer_name, rating, comment, created_at, is_validated) VALUES
('Sophie Martin', 5, 'Menu de Noël absolument parfait ! La dinde était fondante et la bûche délicieuse. Tous nos invités ont adoré. Service impeccable, livraison à l''heure. Je recommande vivement !', '2024-12-26 10:00:00', 1);

-- Avis pour la commande 3 (Marie Dubois - Menu Anniversaire)
INSERT INTO review (customer_name, rating, comment, created_at, is_validated) VALUES
('Marie Dubois', 5, 'Excellent menu végétarien ! Même les non-végétariens ont été conquis. Les asperges rôties étaient un délice. Très bonne expérience, je referai appel à vous.', '2024-10-17 15:30:00', 1);

-- Avis pour la commande 5 (Emma Petit - Menu Barbecue)
INSERT INTO review (customer_name, rating, comment, created_at, is_validated) VALUES
('Emma Petit', 4, 'Très bon menu barbecue, les grillades étaient savoureuses. Seul petit bémol, la livraison a eu 15 minutes de retard mais le résultat était au rendez-vous. Bon rapport qualité-prix.', '2024-07-22 09:00:00', 1);

-- Avis pour la commande 7 (Léa Moreau - Menu Pâques)
INSERT INTO review (customer_name, rating, comment, created_at, is_validated) VALUES
('Léa Moreau', 5, 'Magnifique déjeuner de Pâques ! Le gigot d''agneau était parfaitement cuit et les légumes de saison sublimes. La tarte au citron a fait l''unanimité. Merci pour ce moment !', '2024-04-03 11:00:00', 1);

-- Avis pour la commande 8 (Arthur Simon - Menu Végétarien)
INSERT INTO review (customer_name, rating, comment, created_at, is_validated) VALUES
('Arthur Simon', 5, 'Je suis végétarien et c''est rare de trouver un traiteur qui propose un menu aussi raffiné ! Le risotto aux cèpes était divin. Présentation soignée, saveurs au top. Parfait !', '2024-09-17 14:20:00', 1);

-- Avis non validé (en attente de modération)
INSERT INTO review (customer_name, rating, comment, created_at, is_validated) VALUES
('Client Anonyme', 3, 'Menu correct mais j''attendais mieux pour le prix. Les quantités étaient un peu justes.', '2024-11-28 16:45:00', 0);

-- ============================================================================
-- 16. MISE À JOUR DES COMMANDES AVEC LES AVIS
-- ============================================================================

-- Lier les commandes à leurs avis respectifs
UPDATE `order` SET review_id = 1 WHERE id = 1;
UPDATE `order` SET review_id = 2 WHERE id = 3;
UPDATE `order` SET review_id = 3 WHERE id = 5;
UPDATE `order` SET review_id = 4 WHERE id = 7;
UPDATE `order` SET review_id = 5 WHERE id = 8;

-- ============================================================================
-- FIN DES FIXTURES - Base de données prête à l'emploi !
-- ============================================================================
