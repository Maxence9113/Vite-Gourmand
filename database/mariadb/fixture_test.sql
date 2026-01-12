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

-- Utilisateur test Jean (ROLE_USER)
-- Mot de passe: User1234!@
INSERT INTO user (email, firstname, lastname, roles, password, is_enabled) 
VALUES (
    'user@test.fr',
    'Jean',
    'Dupont',
    '["ROLE_USER"]',
    '$2y$13$LtX3pEAxZx2MzAOn80JzNeWl2gsmmy9QOcqmA2hECM7IrcrIPiZza',
    1
);

-- Allergènes
INSERT INTO allergen (name) VALUES
('Gluten'),
('Fruit de mer'),
('Œufs'),
('Poissons'),
('Arachides'),
('Soja'),
('Lait'),
('Fruits à coque'),
('Céleri'),
('Moutarde'),
('Graines de sésame');

-- Régimes diététiques
INSERT INTO dietetary (name) VALUES
('Végétarien'),
('Végan'),
('Halal'),
('Casher'),
('Sans porc');

-- Thèmes
INSERT INTO theme (name, description) VALUES
('Anniversaire', 'Un menu personnalisable pour célébrer un anniversaire de manière mémorable, adapté à tous les âges.'),
('Barbecue estival', 'Un menu convivial pour profiter des beaux jours en extérieur, avec des grillades et des salades fraîches.'),
('Mariage', 'Un menu élégant et raffiné pour célébrer l\'union de deux personnes, avec des mets d\'exception.'),
('Noël', 'Un menu festif pour célébrer les fêtes de fin d\'année en famille, avec des plats traditionnels et chaleureux.'),
('Réveillon du Nouvel An', 'Un menu festif et élégant pour terminer l\'année en beauté et accueillir la nouvelle année.'),
('Pâques', 'Un menu printanier célébrant le renouveau, avec des saveurs fraîches et des ingrédients de saison.');