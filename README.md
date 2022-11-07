# TRT Conseil
Il s’agit ici d’un projet d’évaluation pour la création d’une application web 
pour une agence de recrutement (TRT Conseil). 

Pour cette évaluation, il m'a été demandé de développer une application 
minimum viable, c'est pourquoi je n'ai pas cherché à réaliser une interface 
graphique poussée.


[![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](https://choosealicense.com/licenses/mit/)


## Pour commencer
Pour utiliser le projet, vous aurez besoin d’installer 
[Composer](https://getcomposer.org/download/) >= 2.4.2 sur votre machine.

### Pré-requis
L'application a besoin des technos suivantes pour fonctionner :
- PHP >= 8.1
- Symfony >= 6.1

Pour la persistance des données, j'ai utilisé MySQL mais vous pouvez utiliser autre chose.

### Installation du projet
Voici les étapes pour installer correctement le projet :

**1. Cloner le projet**
```bash
  git clone https://github.com/devbymoz/trt-conseil.git
```
```bash
  cd trt-conseil
```

*Il faudra faire pointer votre domaine vers le dossier public*

**2. Configuration de la BDD**

Pour configurer la BDD, il faut créer le fichier **.local.env** à la racine du projet, puis ajouter la variable d'environnement `DATABASE_URL`.
Voici un exemple de cette variable avec MySQL :

`DATABASE_URL="mysql://user:password@127.0.0.1:3306/databaseName?serverVersion=5.7"`

*Remplacez user, password et databaseName par les informations de votre BDD.*

**3. Installation les dépendances**

```bash
  composer install
```

Création du fichier HTACCESS
```bash
  composer require symfony/apache-pack
```

**4. Création de la BDD**

Si votre BDD est déjà créée vous pouvez ignorer cette étape.
```bash
  php bin/console doctrine:database:create
```

**5 Création des tables de la BDD**
```bash
  php bin/console doctrine:migrations:migrate
```
Si vous rencontrez une erreur en lancant cette commande, verifiez que votre BDD utilise le même port indiqué dans la variable d'environnement `DATABASE_URL`.

**6. Création des fixtures**

Cette commande permet de créer 245 utilisateurs, 93 entreprises, 150 candidats et 106 offres d'emploi :

```bash
  php bin/console doctrine:fixtures:load --append
```

Pour vous connecter en Admin ou Consultant, utiliser l'identifiant et le mot de passe suivant :
- admin@trt.fr
- consultant@trt.fr
- 123456789

**7. Configuration du Mailer**

L'application utilise le composant mailer de Symfony pour envoyer des mails, je l'ai configuré avec le service de mail Sendinblue mais vous pouvez utiliser [un autre service d'envoi](https://symfony.com/doc/current/mailer.html#transport-setup).

La configuration doit être ajoutée au fichier **.local.env**, voici l'exemple avec Sendinblue ;

`MAILER_DSN=sendinblue+api://yourApiKey@default`
`MAILER_DSN=sendinblue+smtp://yourMail:yourPassword@default`

*Remplacez yourApiKey, yourMail et yourPassword par vos informations Sendinblue.*


**9. Mettez-vous en mode Prodcution**

Une fois vos que vous avez généré vos fixtures, vous pouvez mettre l'application en mode production.

Vous devez ajouter au début de votre fichier .en.local les variables d'environnements suivantes :

`APP_ENV=prod`

`APP_SECRET=719e62d21a7e94a4bf614cc0f6bbe3e6`

*Changez la valeur de l'APP_SECRET par une autre valeur contenant le même nombre de caractère.*


Les étapes d'installations sont términées, vous êtes prêt à utiliser l'application, vous pouvez consuler le manuel d'utilisation si vous avez un problème.
## Documentation

- [Manuel d'utilisation ](https://github.com/devbymoz/trt-conseil/tree/main/public/pdf/documentation-trtconseil.pdf)


## Auteur

👨🏻‍💻 Mohamed Zaoui [@devbymoz](https://github.com/devbymoz)