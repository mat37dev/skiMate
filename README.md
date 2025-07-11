# Back

## Installation

Ce projet utilise Docker pour lancer l’environnement backend, incluant PHP (Symfony), MongoDB, PostgreSQL (branche `dev`), PhpMyAdmin et Mongo Express.

### Prérequis

- [Docker](https://www.docker.com/) installé et fonctionnel

---

## Étapes

1. **Basculer sur la branche `dev` :**

```bash
git checkout dev
```

2. **Lancer les conteneurs avec build :**

```bash
docker compose up --build
```

3. **Injection des données MongoDB**

Pour injecter le fichier `stations2.json` dans la base MongoDB (`skiMate`), procédez ainsi sous **PowerShell** :

- Vérifiez l'ID de votre conteneur Mongo :

```powershell
docker ps
```

(exemple : `81ac703d2fac5bc04df70b83d97fe80cbcf8cc92f16a0809b2aba6e449d9342d`)

- Assurez-vous que votre `.env` contient :

```env
MONGODB_URL=mongodb://skimate:skimate@mongo:27017
MONGODB_DB=skiMate
```

- Importer les données avec une image temporaire officielle :

```powershell
docker run --rm -v "$($env:USERPROFILE)\PATH_TO_YOUR_DATA_FILE\stations.json:/stations.json" mongo mongoimport --host host.docker.internal --port 27017 --username skimate --password skimate --authenticationDatabase admin --db skiMate --collection stations --file /stations.json 
```


✅ Vous verrez un message comme : `3 document(s) imported successfully.`

4. **Lancer le serveur PHP dans le conteneur PHP :**

```bash
docker exec -it CONTAINER_ID bash
cd /var/www/html
php -S 0.0.0.0:8000 -t public
```

> L'application Symfony sera alors accessible sur [http://localhost:8000](http://localhost:8000)

5. **Lancer les migrations Doctrine**

Toujours dans le conteneur `php-1` :

```bash
php bin/console doctrine:migrations:migrate
```

---

## Services exposés

- **PHP / Symfony** → http://localhost:8000
- **PhpMyAdmin** → http://localhost:8081
- **Mongo Express** → http://localhost:8082
- **MongoDB** → port 27017
- **PostgreSQL / MySQL** → selon config du docker-compose

---

## Dépannage


### ❌ Erreur : `mongodb` extension non reconnue dans PHP

Ajoutez les deux lignes suivantes dans le `Dockerfile` :

```Dockerfile
&& pecl install mongodb \
&& echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini \
```

---

### ❌ Erreur 500 au démarrage de MySQL dans Docker

Cela peut être dû à une instance MySQL locale déjà active. Fermez-la avant de relancer Docker :

```bash
docker compose up --build
```
