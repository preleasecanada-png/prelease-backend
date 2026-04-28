# Configuration de l'Assistant Virtuel - Prelease Canada

Ce document explique comment configurer l'assistant virtuel avec support pour les messages et appels téléphoniques.

## Vue d'ensemble

L'assistant virtuel utilise:
- **OpenAI GPT-4** pour générer des réponses intelligentes
- **Twilio** pour les SMS et appels téléphoniques
- **Laravel Backend** pour la gestion des conversations
- **React Frontend** pour l'interface de chat

## Variables d'Environnement Requises

Ajoutez les variables suivantes à votre fichier `.env` dans le backend:

```env
# OpenAI Configuration
OPENAI_API_KEY=sk-your-openai-api-key-here

# Twilio Configuration
TWILIO_ACCOUNT_SID=your-twilio-account-sid
TWILIO_AUTH_TOKEN=your-twilio-auth-token
TWILIO_PHONE_NUMBER=+1234567890
TWILIO_API_KEY_SID=your-twilio-api-key-sid
TWILIO_API_KEY_SECRET=your-twilio-api-key-secret
```

### Obtention des Clés API

#### OpenAI API Key
1. Allez sur https://platform.openai.com/api-keys
2. Connectez-vous à votre compte OpenAI
3. Cliquez sur "Create new secret key"
4. Copiez la clé et ajoutez-la à `OPENAI_API_KEY`

#### Twilio Configuration
1. Allez sur https://www.twilio.com/console
2. Connectez-vous à votre compte Twilio
3. Dans le tableau de bord, trouvez:
   - **Account SID**: Copiez vers `TWILIO_ACCOUNT_SID`
   - **Auth Token**: Copiez vers `TWILIO_AUTH_TOKEN`
4. Achetez un numéro de téléphone Twilio et copiez-le vers `TWILIO_PHONE_NUMBER`
5. Pour les appels vocaux, créez une clé API:
   - Allez dans Settings > API Keys
   - Cliquez sur "Create new API Key"
   - Copiez le SID vers `TWILIO_API_KEY_SID`
   - Copiez le Secret vers `TWILIO_API_KEY_SECRET`

## Migration de la Base de Données

Exécutez les migrations pour créer les tables nécessaires:

```bash
php artisan migrate
```

Cela créera les tables suivantes:
- `virtual_assistant_conversations` - Stocke les conversations
- `virtual_assistant_messages` - Stocke les messages
- `virtual_assistant_settings` - Stocke les paramètres de configuration

## Configuration des Webhooks Twilio

Configurez les webhooks Twilio pour recevoir les SMS et appels:

### SMS Webhook
1. Allez dans votre console Twilio
2. Naviguez vers Messaging > Settings > Messaging Services
3. Sélectionnez votre service de messagerie
4. Dans "Messaging Service Settings", configurez:
   - **When a message comes in**: `https://your-domain.com/api/twilio/sms`
   - **Request type**: HTTP POST

### Voice Webhook
1. Allez dans votre console Twilio
2. Naviguez vers Phone Numbers > Active Numbers
3. Sélectionnez votre numéro de téléphone
4. Dans "Voice & Fax", configurez:
   - **A call comes in**: `https://your-domain.com/api/twilio/call`
   - **Request type**: HTTP POST

## Configuration des Paramètres

Les paramètres peuvent être configurés via:
1. L'interface d'administration: `/admin/virtual-assistant`
2. Directement dans la base de données via le modèle `VirtualAssistantSetting`

### Paramètres Disponibles

| Clé | Type | Défaut | Description |
|-----|------|--------|-------------|
| `ai_provider` | string | openai | Fournisseur AI (openai, anthropic) |
| `ai_model` | string | gpt-4 | Modèle AI à utiliser |
| `system_prompt` | text | - | Prompt système pour l'assistant |
| `max_tokens` | integer | 1000 | Tokens max pour les réponses |
| `temperature` | float | 0.7 | Température des réponses (0-2) |
| `enable_voice` | boolean | true | Activer le support vocal |
| `enable_sms` | boolean | true | Activer le support SMS |
| `business_hours_start` | string | 09:00 | Heure de début des heures de bureau |
| `business_hours_end` | string | 18:00 | Heure de fin des heures de bureau |

## Endpoints API

### Authentifiés (nécessitent un token)

#### Démarrer une conversation
```
POST /api/virtual-assistant/start
Headers: Authorization: Bearer {token}
Body: {
  "channel": "chat", // ou "sms", "phone"
  "phone_number": "+1234567890", // optionnel
  "subject": "Sujet de la conversation" // optionnel
}
```

#### Envoyer un message
```
POST /api/virtual-assistant/send-message
Headers: Authorization: Bearer {token}
Body: {
  "conversation_id": 1,
  "message": "Votre message ici"
}
```

#### Obtenir les conversations
```
GET /api/virtual-assistant/conversations
Headers: Authorization: Bearer {token}
```

#### Obtenir une conversation spécifique
```
GET /api/virtual-assistant/conversations/{id}
Headers: Authorization: Bearer {token}
```

#### Fermer une conversation
```
POST /api/virtual-assistant/conversations/{id}/close
Headers: Authorization: Bearer {token}
```

#### Obtenir les paramètres
```
GET /api/virtual-assistant/settings
Headers: Authorization: Bearer {token}
```

#### Mettre à jour les paramètres
```
POST /api/virtual-assistant/settings
Headers: Authorization: Bearer {token}
Body: {
  "settings": [
    {
      "key": "ai_model",
      "value": "gpt-4-turbo",
      "type": "string"
    }
  ]
}
```

### Webhooks Twilio (sans authentification)

#### Webhook SMS entrant
```
POST /api/twilio/sms
Body: Twilio SMS webhook payload
```

#### Webhook appel entrant
```
POST /api/twilio/call
Body: Twilio call webhook payload
```

#### Webhook saisie vocale
```
POST /api/twilio/gather
Body: Twilio gather webhook payload
```

## Frontend

### Page de Chat
Accédez à l'assistant virtuel via: `/virtual-assistant`

### Page d'Administration
Configurez les paramètres via: `/admin/virtual-assistant`

## Fonctionnalités

### Chat en Temps Réel
- Conversation avec l'assistant AI via l'interface web
- Historique des conversations
- Support multi-langues (français/anglais)

### Support SMS
- Les utilisateurs peuvent envoyer des SMS au numéro Twilio configuré
- L'assistant répond automatiquement via SMS
- Respect des heures de bureau

### Support Téléphonique
- Les utilisateurs peuvent appeler le numéro Twilio
- Reconnaissance vocale pour les questions
- Synthèse vocale pour les réponses
- Respect des heures de bureau

### Heures de Bureau
- En dehors des heures configurées, les réponses sont différées
- Message automatique informant des heures de bureau
- Configuration flexible via l'admin

## Sécurité

- Toutes les conversations sont liées aux utilisateurs authentifiés
- Les webhooks Twilio valident les numéros de téléphone
- Les clés API sont stockées dans les variables d'environnement
- Les messages sont sauvegardés dans la base de données

## Dépannage

### L'assistant ne répond pas
1. Vérifiez que `OPENAI_API_KEY` est correctement configuré
2. Vérifiez les logs Laravel pour les erreurs
3. Assurez-vous que les migrations ont été exécutées

### Les SMS ne fonctionnent pas
1. Vérifiez que les variables Twilio sont correctes
2. Vérifiez que le webhook SMS est configuré dans Twilio
3. Assurez-vous que le numéro Twilio est actif

### Les appels ne fonctionnent pas
1. Vérifiez que `enable_voice` est activé dans les paramètres
2. Vérifiez que les clés API Twilio sont correctes
3. Vérifiez que le webhook d'appel est configuré dans Twilio

### Erreur "User not found" (SMS/Call)
1. Assurez-vous que le numéro de téléphone de l'utilisateur est enregistré dans la base de données
2. Le numéro doit être au format international (+1 pour le Canada)

## Support

Pour toute question ou problème, contactez l'équipe technique de Prelease Canada.
