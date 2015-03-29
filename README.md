# RSS Reader
## 1. Couche Model
### 1.1. ORM
Les 3 classes principales de cette couche sont :

- `Schema`: A partir d'une `Connection`, cette classe scanne les tables de la base de données et stock les champs de chaque table et ses relations avec les autres tables. Elle se base sur la convention qu'un champs nommé "foo_id" est une clé étrangère sur la table "foo".

- `Mapper`: peut persister ou supprimer toute classe qui hérite de `Model` avec ses relations (Persister une `Channel` persistera aussi les `Item`s associés)

- `Finder`: Cette classe prend parmi les paramètres du constructeur la classe model correspondante et donne une instance qui peut lire ce model avec ces relations avec des fonctionnalités de tri (sortBy()) et de filtrage (where()). Le nombre maximal des recurrences à faire pour lire les models associés est paramètrable, il est par défaut à 1 (Seulement l'entité et les entités directement associées sont lus).

### 1.2. Les classes models
Elles héritent de `Model` est respèctent les conventions suivants:

- Le nom de la classe en Capital CamlCase correspond au nom de la table en Underscores (Item => item, FeedElement => feed_element)

- Les attributs sont publiques, leurs noms en camlCase correspond aux noms des champs de la table en Underscores.

- Des attributs sont ajoutés pour les entités reliés portant les mêmes noms des classes avec un 's' s'il s'agit d'une collection ( `Channel` possède un attribut $category et un attribut $items )

## 2. le Worker
Symfony Console Component a été utilisé dans cette couche avec des classes qui font le mapping entre la structrue XML de RSS et Atom et les classes models. Pour ne pas dupliquer les items, un test du hash md5 du fichier est fait au début, puis les links sont verifiés. Le choix du link comme identifiant a été fait car c'est le seul champ obligatoire dans les deux standards RSS et Atom.

## 3. Couche présentation
Cette couche utilise Silex avec Twig pour le templating et Monolog pour les fichiers du log. Au niveau client on a utilisé la bibliothèque jQuery pour faire les appels Ajax ( pour pouvoir envoyer des requètes PUT et DELETE ) et aussi Bootstrap pour le CSS.

L'application we offre un API REST qui accèpte les paramètres sous les deux formats `application/x-www-form-urlencoded` et `application/json`. Il s'adapte à la requète en lisant le champ `Accept` de la requète et produisant la réponse sous format HTML ou JSON.

## 4. Les tests
### 4.1. Les tests unitaires
Les tests unitaires ont été fait par le framwork PHPUnit. La couche model et La classe `Fetcher` (sous laquelle se base le worker) ont été testées. Le fichier `phpunit.xml` contient des variables de configuration de la base de données sur laquelle les tests vont être lancés.
### 4.2. Les tests fonctionnelles
Pour les tests fonctionnelles on voullait utiliser le framework Behat avec l'extention Mink pour simuler le navigateur. Mais on n'a pas abouti !

# Etapes de l'installation

1. Créer deux bases de données MySQL (une est pour les tests) et importer le fichier `database.sql`

2. Changer les fichiers `config.php` et `phpunit.xml`

3. Télécharger les dépendences : `composer install`

4. Créer un cron job qui lance le script `worker` chaque 30 minutes ou lancer le script `run`

5. Se déplacer dans le dossier `/public` et lancer la commande `php -S localhost:8080`

6. Accéder à l'application par le lien `http://localhost:8080/`

# Remarque
- Deux exemples de flux rss seront dans les liens http://localhost:8080/demo/rss.xml et http://localhost:8080/demo/atom.xml