<?php
declare(strict_types=1);

use Cake\Core\Configure;

/**
 * Public privacy and advertising disclosure page.
 *
 * @var \App\View\AppView $this
 */

$this->assign('title', 'Privacy Policy');
$supportEmail = trim((string)Configure::read('SiteOptions.support_email', ''));
?>

<article class="rh-privacy-page py-4">
    <header class="mb-4">
        <h1>Privacy Policy</h1>
        <p class="text-muted mb-0">Effective date: <?= h(date('F j, Y')) ?></p>
    </header>

    <p>
        RacerHistory is an independent website about Murray State Men's Basketball history.
        This policy explains what information may be collected when you use this website,
        how cookies and similar technologies are used, and the choices available to you.
    </p>

    <section class="mb-4" aria-labelledby="information-collected-heading">
        <h2 id="information-collected-heading">Information We Collect</h2>
        <p>
            We may receive information that you submit when you create or use an account,
            contact the site, or interact with features that require a signed-in user.
            We may also receive ordinary technical information needed to operate and secure
            the site, such as your IP address, browser type, device information, requested
            pages, timestamps, and referring URLs.
        </p>
        <p>
            We use this information to provide the site, authenticate users, protect the
            service from abuse, maintain security, understand site performance, and respond
            to requests.
        </p>
    </section>

    <section class="mb-4" aria-labelledby="cookies-heading">
        <h2 id="cookies-heading">Cookies and Similar Technologies</h2>
        <p>
            Cookies are small files stored by your browser. Similar technologies may include
            pixels, local storage, and other identifiers. The site uses the following categories:
        </p>
        <dl>
            <dt>Necessary technologies</dt>
            <dd>
                These support login sessions, security, CSRF protection, consent preferences,
                and core site operation. They cannot be disabled through the optional consent
                controls because the site may not work correctly without them. Our consent
                manager stores your choice in the <code>cc_cookie</code> cookie unless its
                configuration is changed.
            </dd>
            <dt>Analytics technologies</dt>
            <dd>
                If analytics is enabled for this site, this category may measure visits,
                navigation, and performance so we can improve the service. Analytics is not
                enabled unless you choose the Analytics category in the consent dialog.
            </dd>
            <dt>Advertising technologies</dt>
            <dd>
                If advertising is enabled and you consent to the Advertising category, ad
                providers may use cookies or similar technologies to deliver, measure, secure,
                and limit the frequency of ads. Advertising is not loaded before the applicable
                consent choice is available.
            </dd>
        </dl>
    </section>

    <section class="mb-4" aria-labelledby="google-advertising-heading">
        <h2 id="google-advertising-heading">Google AdSense and Google Ad Manager</h2>
        <p>
            Depending on the ad unit configuration, RacerHistory may use Google AdSense and/or
            Google Ad Manager, including Google Publisher Tag (GPT), to request, deliver, measure,
            and protect advertising. Google and other third-party vendors may use cookies to serve
            ads based on a user's prior visits to this website or other websites.
        </p>
        <p>
            Google's use of advertising cookies enables Google and its partners to serve ads to
            users based on their visit to this site and/or other sites on the Internet. These
            technologies may also support ad measurement, frequency capping, fraud prevention,
            security, and reporting. The exact cookies and vendors can vary by the ad demand,
            geography, device, and settings selected in the publisher's Google account.
        </p>
        <p>
            Google may set or read advertising cookies, including cookies associated with
            Google's advertising services such as the DoubleClick cookie, when an ad request is
            eligible and the required consent is present. Google describes its cookie use in
            <a href="https://policies.google.com/technologies/cookies" target="_blank" rel="noopener noreferrer">How Google uses cookies</a>.
        </p>
        <p>
            When consent is not granted, the site requests non-personalized advertising where
            the configured Google product supports it. Non-personalized ads may still use
            cookies or similar technologies for contextual delivery, frequency capping,
            aggregated measurement, security, and fraud prevention as allowed by the provider
            and applicable settings.
        </p>
    </section>

    <section class="mb-4" aria-labelledby="ad-partners-heading">
        <h2 id="ad-partners-heading">Google Advertising Partners</h2>
        <p>
            Google AdSense and Google Ad Manager can involve Google and other ad technology
            partners selected in the publisher's account. Those partners may process information
            needed to serve or measure an ad, subject to their own privacy policies and available
            consent controls.
        </p>
        <p>
            The publisher must keep the actual Google ad technology partner list used by this
            website current. For the current account-specific list and partner controls, see
            Google's guidance for
            <a href="https://support.google.com/adsense/answer/9012903" target="_blank" rel="noopener noreferrer">AdSense ad technology partners</a>
            and
            <a href="https://support.google.com/admanager/answer/7673898" target="_blank" rel="noopener noreferrer">Ad Manager ad technology partners</a>.
            Partner privacy policies and opt-out choices are available through that list.
        </p>
    </section>

    <section class="mb-4" aria-labelledby="your-choices-heading">
        <h2 id="your-choices-heading">Your Choices</h2>
        <p>
            You can change optional cookie choices at any time using the consent controls.
            <a href="#privacy-preferences" data-action="click->consent#showPreferences">Manage privacy choices</a>.
            Rejecting optional categories does not remove necessary cookies required for site
            operation.
        </p>
        <p>
            You may also opt out of personalized advertising through
            <a href="https://www.google.com/settings/ads" target="_blank" rel="noopener noreferrer">Google Ads Settings</a>.
            You can opt out of some third-party vendors' use of cookies for personalized
            advertising through
            <a href="https://www.aboutads.info/choices/" target="_blank" rel="noopener noreferrer">aboutads.info</a>.
            These tools are operated by third parties and their availability or effect may
            depend on your browser and region.
        </p>
    </section>

    <section class="mb-4" aria-labelledby="third-party-links-heading">
        <h2 id="third-party-links-heading">Third-Party Services</h2>
        <p>
            Google and other vendors linked from this policy operate independently. Their
            processing is governed by their own policies, including
            <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Google's Privacy Policy</a>.
            We do not control third-party cookies once they are set by an eligible provider.
        </p>
    </section>

    <section class="mb-4" aria-labelledby="contact-heading">
        <h2 id="contact-heading">Contact</h2>
        <?php if ($supportEmail !== '') : ?>
        <p>
            For questions about this policy or the site's privacy practices, contact the
            RacerHistory site administrator at
            <a href="mailto:<?= h($supportEmail) ?>"><?= h($supportEmail) ?></a>.
        </p>
        <?php else : ?>
        <p>
            For questions about this policy or the site's privacy practices, contact the
            RacerHistory site administrator.
        </p>
        <?php endif; ?>
    </section>

    <p class="small text-muted mb-0">
        This page describes the current site configuration and is not legal advice. The site
        operator is responsible for keeping this policy, consent configuration, and Google ad
        technology partner disclosures accurate as services change.
    </p>
</article>
