-- SQL script to configure SSO for the iSDR application
-- This script generates an SSO secret and configures the callback URL

DO $$
DECLARE
    v_secret TEXT;
BEGIN
    -- Generate a random secret (simulated, you will need to replace with a real value)
    -- To generate a real secret: bin2hex(random_bytes(32)) in PHP
    -- or use: openssl rand -hex 32
    v_secret := 'votre_secret_genere_ici_64_caracteres_minimum_cryptographiquement';
    
    -- Update the iSDR application
    UPDATE application 
    SET 
        sso_secret = v_secret,
        sso_callback_url = 'http://localhost:3000/auth/callback'
    WHERE name = 'iSDR';
    
    -- Display the result
    RAISE NOTICE 'SSO configuration for iSDR has been updated';
    RAISE NOTICE 'SSO Secret: %', v_secret;
    RAISE NOTICE 'Callback URL: http://localhost:3000/auth/callback';
END $$;

-- Verify the configuration
SELECT id, name, sso_secret, sso_callback_url 
FROM application 
WHERE name = 'iSDR';
