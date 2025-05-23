<template>
    <PageTemplate>
        <Head title="Terms of Use - BostonScope" />

        <div class="bg-gray-50 py-12 sm:py-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto">
                    <h1 class="text-4xl font-bold tracking-tight text-center text-gray-900 mb-4">
                        BostonScope Terms of Use
                    </h1>
                    <p class="text-center text-gray-600 mb-10">
                        Effective Date: {{ effectiveDate }}
                    </p>

                    <div class="prose prose-lg lg:prose-xl max-w-none bg-white p-6 sm:p-8 rounded-lg shadow-lg">
                        <p class="lead">
                            Welcome to BostonScope! These Terms of Use ("Terms") govern your access to and use of the BostonScope website, services, applications, and content (collectively, the "Service") provided by AlcivarTech LLC ("AlcivarTech," "we," "us," or "our").
                        </p>
                        <p>
                            Please read these Terms carefully before using the Service.
                        </p>

                        <h2>1. Agreement to Terms</h2>
                        <p>
                            By accessing or using the Service, creating an account, or by clicking to accept or agree to the Terms when this option is made available to you, you accept and agree to be bound and abide by these Terms and our Privacy Policy, found at <Link :href="route('privacy.policy')" class="text-blue-600 hover:text-blue-800 underline">our Privacy Policy page</Link> and incorporated herein by reference. If you do not agree to these Terms or the Privacy Policy, you must not access or use the Service.
                        </p>
                        <p>
                            If you are using the Service on behalf of an organization or entity ("Organization"), then you are agreeing to these Terms on behalf of that Organization, and you represent and warrant that you have the authority to bind the Organization to these Terms. In that case, "you" and "your" will refer to that Organization.
                        </p>

                        <h2>2. Description of Service</h2>
                        <p>
                            BostonScope provides users with access to aggregated public data related to city operations, including but not limited to crime reports, 311 service cases, building permits, and property violations, presented through an interactive map interface primarily focused on the Boston area. The Service may also include AI-generated summaries and insights based on this data, as well as features for registered users such as saving locations and receiving periodic email reports. Features and data availability may vary depending on your subscription plan.
                        </p>

                        <h2>3. User Accounts</h2>
                        <p>
                            a.  <strong>Registration:</strong> To access certain features of the Service, you may be required to register for an account. When you register, you agree to provide accurate, current, and complete information about yourself as prompted by our registration form and to maintain and promptly update such information to keep it accurate, current, and complete.
                        </p>
                        <p>
                            b.  <strong>Account Security:</strong> You are responsible for safeguarding the password that you use to access the Service (if applicable, for non-social logins) and for any activities or actions under your account. We encourage you to use "strong" passwords (passwords that use a combination of upper and lower case letters, numbers, and symbols) with your account. AlcivarTech LLC cannot and will not be liable for any loss or damage arising from your failure to comply with the above. You agree to notify us immediately of any unauthorized use of your account.
                        </p>
                        <p>
                            c.  <strong>Social Logins:</strong> If you choose to register or log in using a third-party service (e.g., Google), you authorize us to access and use your information from that service as permitted by that service, and to store your log-in credentials for that service.
                        </p>

                        <h2>4. Subscription Terms (If Applicable)</h2>
                        <p>
                            a.  <strong>Subscription Plans:</strong> We offer various subscription plans for enhanced features of the Service ("Subscription Plans"). Details of current Subscription Plans, including pricing and features, are available on our website at <Link :href="route('subscription.index')" class="text-blue-600 hover:text-blue-800 underline">our Subscription page</Link>.
                        </p>
                        <p>
                            b.  <strong>Fees and Payment:</strong> You agree to pay all applicable fees related to your Subscription Plan in accordance with the billing terms in effect at the time a fee or charge is due and payable. We use a third-party payment processor (e.g., Stripe) to bill you through a payment account linked to your account on the Service. The processing of payments will be subject to the terms, conditions, and privacy policies of the Payment Processor in addition to these Terms. We are not responsible for errors by the Payment Processor.
                        </p>
                        <p>
                            c.  <strong>Automatic Renewal:</strong> Unless you cancel your subscription before the end of the current subscription period, your subscription will automatically renew for an additional period of the same duration as your initial term, and you authorize us (through our Payment Processor) to charge your payment method for the renewal term at the then-current non-promotional subscription rate.
                        </p>
                        <p>
                            d.  <strong>Cancellation:</strong> You may cancel your subscription at any time through your account settings (typically accessible via a "Billing Portal" link) or by contacting us at help@bostonscope.com. Cancellations will take effect at the end of your current billing period. <strong>No refunds or credits will be provided for partial subscription periods or unused services, except as may be required by applicable law.</strong>
                        </p>
                        <p>
                            e.  <strong>Fee Changes:</strong> We reserve the right to change our subscription fees upon reasonable prior notice to you. Any fee changes will become effective at the end of the then-current billing cycle. Your continued use of the Service after the fee change comes into effect constitutes your agreement to pay the modified subscription fee amount.
                        </p>
                        <p>
                            f.  <strong>Free Trials:</strong> We may, at our sole discretion, offer subscriptions with a free trial for a limited period. Unless you cancel before the end of the free trial, you will be automatically charged the applicable subscription fee for the selected plan.
                        </p>

                        <h2>5. License to Use the Service</h2>
                        <p>
                            a.  <strong>Grant of License:</strong> Subject to your compliance with these Terms, AlcivarTech LLC grants you a limited, non-exclusive, non-transferable, non-sublicensable, revocable license to access and use the Service for your personal, non-commercial use, or for your internal business purposes if you are subscribed to a plan that permits such use, strictly in accordance with these Terms and the features of your chosen Subscription Plan.
                        </p>
                        <p>
                            b.  <strong>Restrictions:</strong> Under this license, you may not:
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Modify, copy, distribute, transmit, display, perform, reproduce, publish, license, create derivative works from, transfer, or sell any information, software, products, or services obtained from the Service, except as expressly permitted by your Subscription Plan or these Terms.</li>
                                <li>Use the Service or its content for any commercial purpose not expressly permitted by your Subscription Plan.</li>
                                <li>Use the Service for any public display (commercial or non-commercial) not expressly permitted.</li>
                                <li>Attempt to decompile, reverse engineer, disassemble, or otherwise derive the source code for any software forming part of the Service.</li>
                                <li>Remove, alter, or obscure any copyright, trademark, or other proprietary notices from the Service or content available through it.</li>
                                <li>Transfer your access to the Service to another person or "mirror" the Service or its content on any other server without our express written permission.</li>
                                <li>Use any robot, spider, scraper, or other automated means to access the Service for any purpose without our express written permission, or bypass any measures we may use to prevent or restrict access to the Service.</li>
                                <li>Use the Service in any manner that could disable, overburden, damage, or impair the Service or interfere with any other party's use of the Service.</li>
                            </ul>
                        </p>
                        <p>
                            c.  <strong>Termination of License:</strong> This license shall automatically terminate if you violate any of these restrictions. AlcivarTech LLC may also terminate this license at any time, with or without cause, in its sole discretion. Upon termination of your license, you must cease all use of the Service.
                        </p>

                        <h2>6. Intellectual Property Rights</h2>
                        <p>
                            The Service and its entire contents, features, and functionality (including but not limited to all information, software, text, displays, images, video, and audio, and the design, selection, and arrangement thereof, AI-generated summaries and analyses created by our systems, and all underlying data compilations and arrangements) are owned by AlcivarTech LLC, its licensors, or other providers of such material and are protected by United States and international copyright, trademark, patent, trade secret, and other intellectual property or proprietary rights laws. These Terms grant you a limited license to use the Service as described herein and do not transfer any ownership rights. "BostonScope" and all related names, logos, product and service names, designs, and slogans are trademarks of AlcivarTech LLC or its affiliates or licensors. You must not use such marks without the prior written permission of AlcivarTech LLC.
                        </p>

                        <h2>7. Third-Party Data and Services</h2>
                        <p>
                            a.  <strong>Data Sources:</strong> The Service utilizes data aggregated from various publicly available third-party sources. While we strive to provide accurate and timely information, AlcivarTech LLC does not verify, endorse, or guarantee the accuracy, completeness, timeliness, or reliability of any data or information provided by these third-party sources.
                        </p>
                        <p>
                            b.  <strong>Disclaimer:</strong> You acknowledge that the data presented through the Service is based on information provided by third parties and is subject to change without notice. AlcivarTech LLC is not responsible for any errors, omissions, or inaccuracies in such third-party data, nor for any decisions made or actions taken in reliance thereon. The use of such data is at your own risk.
                        </p>
                        <p>
                            c.  <strong>Third-Party Links:</strong> The Service may contain links to third-party websites or services that are not owned or controlled by AlcivarTech LLC. We have no control over, and assume no responsibility for, the content, privacy policies, or practices of any third-party websites or services.
                        </p>

                        <h2>8. AI-Generated Content</h2>
                        <p>
                            Portions of the Service, including summaries and insights, may be generated by artificial intelligence (AI) models. You acknowledge and agree that:
                            <ul class="list-disc pl-5 space-y-1">
                                <li>AI-generated content is provided for informational purposes only and may contain errors, inaccuracies, or omissions.</li>
                                <li>AI models are constantly evolving, and the output may vary.</li>
                                <li>AlcivarTech LLC makes no representations or warranties regarding the accuracy, reliability, or completeness of AI-generated content.</li>
                                <li>You should not rely solely on AI-generated content for making decisions and should independently verify any information before acting upon it.</li>
                                <li>We are not liable for any actions taken or decisions made based on AI-generated content.</li>
                            </ul>
                        </p>

                        <h2>9. Disclaimer of Warranties</h2>
                        <p>
                            THE SERVICE AND ALL INFORMATION, CONTENT, MATERIALS, PRODUCTS (INCLUDING SOFTWARE) AND OTHER SERVICES INCLUDED ON OR OTHERWISE MADE AVAILABLE TO YOU THROUGH THE SERVICE ARE PROVIDED BY ALCIVARTECH LLC ON AN "AS IS" AND "AS AVAILABLE" BASIS, UNLESS OTHERWISE SPECIFIED IN WRITING. ALCIVARTECH LLC MAKES NO REPRESENTATIONS OR WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, AS TO THE OPERATION OF THE SERVICE, OR THE INFORMATION, CONTENT, MATERIALS, PRODUCTS (INCLUDING SOFTWARE) OR OTHER SERVICES INCLUDED ON OR OTHERWISE MADE AVAILABLE TO YOU THROUGH THE SERVICE, UNLESS OTHERWISE SPECIFIED IN WRITING. YOU EXPRESSLY AGREE THAT YOUR USE OF THE SERVICE IS AT YOUR SOLE RISK.
                        </p>
                        <p>
                            TO THE FULL EXTENT PERMISSIBLE BY LAW, ALCIVARTECH LLC DISCLAIMS ALL WARRANTIES, EXPRESS OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, TITLE, AND NON-INFRINGEMENT. ALCIVARTECH LLC DOES NOT WARRANT THAT THE SERVICE, INFORMATION, CONTENT, MATERIALS, PRODUCTS (INCLUDING SOFTWARE) OR OTHER SERVICES INCLUDED ON OR OTHERWISE MADE AVAILABLE TO YOU THROUGH THE SERVICE, ITS SERVERS, OR ELECTRONIC COMMUNICATIONS SENT FROM ALCIVARTECH LLC ARE FREE OF VIRUSES OR OTHER HARMFUL COMPONENTS.
                        </p>

                        <h2>10. Limitation of Liability</h2>
                        <p>
                            TO THE FULL EXTENT PERMISSIBLE BY LAW, IN NO EVENT SHALL ALCIVARTECH LLC, ITS AFFILIATES, OR THEIR LICENSORS, SERVICE PROVIDERS, EMPLOYEES, AGENTS, OFFICERS, OR DIRECTORS BE LIABLE FOR DAMAGES OF ANY KIND, UNDER ANY LEGAL THEORY, ARISING OUT OF OR IN CONNECTION WITH YOUR USE, OR INABILITY TO USE, THE SERVICE, ANY WEBSITES LINKED TO IT, ANY CONTENT ON THE SERVICE OR SUCH OTHER WEBSITES, INCLUDING ANY DIRECT, INDIRECT, SPECIAL, INCIDENTAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING BUT NOT LIMITED TO, PERSONAL INJURY, PAIN AND SUFFERING, EMOTIONAL DISTRESS, LOSS OF REVENUE, LOSS OF PROFITS, LOSS OF BUSINESS OR ANTICIPATED SAVINGS, LOSS OF USE, LOSS OF GOODWILL, LOSS OF DATA, AND WHETHER CAUSED BY TORT (INCLUDING NEGLIGENCE), BREACH OF CONTRACT, OR OTHERWISE, EVEN IF FORESEEABLE.
                        </p>
                        <p>
                            THE FOREGOING DOES NOT AFFECT ANY LIABILITY THAT CANNOT BE EXCLUDED OR LIMITED UNDER APPLICABLE LAW. IN JURISDICTIONS THAT DO NOT ALLOW THE EXCLUSION OR LIMITATION OF LIABILITY FOR CONSEQUENTIAL OR INCIDENTAL DAMAGES, OUR LIABILITY IS LIMITED TO THE MAXIMUM EXTENT PERMITTED BY LAW.
                        </p>
                        <p>
                            IN NO EVENT SHALL THE AGGREGATE LIABILITY OF ALCIVARTECH LLC AND ITS AFFILIATES EXCEED THE GREATER OF ONE HUNDRED U.S. DOLLARS (USD $100.00) OR THE AMOUNT YOU PAID ALCIVARTECH LLC, IF ANY, IN THE PAST SIX (6) MONTHS FOR THE SERVICES GIVING RISE TO THE CLAIM.
                        </p>

                        <h2>11. Indemnification</h2>
                        <p>
                            You agree to defend, indemnify, and hold harmless AlcivarTech LLC, its affiliates, licensors, and service providers, and its and their respective officers, directors, employees, contractors, agents, licensors, suppliers, successors, and assigns from and against any claims, liabilities, damages, judgments, awards, losses, costs, expenses, or fees (including reasonable attorneys' fees) arising out of or relating to your violation of these Terms or your use of the Service, including, but not limited to, any use of the Service's content, services, and products other than as expressly authorized in these Terms or your use of any information obtained from the Service.
                        </p>

                        <h2>12. Modifications to the Service and Terms</h2>
                        <p>
                            AlcivarTech LLC reserves the right to withdraw or amend this Service, and any service or material we provide on the Service, in our sole discretion without notice (except as may be required for material changes to paid Subscription Plans). We will not be liable if for any reason all or any part of the Service is unavailable at any time or for any period.
                        </p>
                        <p>
                            We may revise and update these Terms of Use from time to time in our sole discretion. All changes are effective immediately when we post them and apply to all access to and use of the Service thereafter. For material changes to subscribers, we will provide reasonable notice (e.g., by email or a notice on the Service). Your continued use of the Service following the posting of revised Terms of Use means that you accept and agree to the changes. You are expected to check this page frequently so you are aware of any changes, as they are binding on you.
                        </p>

                        <h2>13. Term and Termination</h2>
                        <p>
                            a.  <strong>Term:</strong> These Terms will remain in full force and effect while you use the Service.
                        </p>
                        <p>
                            b.  <strong>Termination by You:</strong> You may terminate your account and these Terms at any time by discontinuing use of the Service and, if you are a subscriber, by canceling your subscription as described in Section 4(d).
                        </p>
                        <p>
                            c.  <strong>Termination by AlcivarTech LLC:</strong> We may suspend or terminate your access to all or any part of the Service at any time, with or without cause, with or without notice, effective immediately. Grounds for such termination may include, but are not limited to, (i) a breach of these Terms, (ii) a request by law enforcement or other government agencies, (iii) a request by you (self-initiated account deletions), (iv) discontinuance or material modification of the Service, or (v) unexpected technical or security issues or problems.
                        </p>
                        <p>
                            d.  <strong>Effect of Termination:</strong> Upon termination, your right to use the Service will immediately cease. If your account is terminated, AlcivarTech LLC may, in its sole discretion, delete any data associated with your account. All provisions of these Terms which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity, and limitations of liability.
                        </p>

                        <h2>14. Governing Law and Jurisdiction</h2>
                        <p>
                            All matters relating to the Service and these Terms, and any dispute or claim arising therefrom or related thereto (in each case, including non-contractual disputes or claims), shall be governed by and construed in accordance with the internal laws of the State of Massachusetts, USA, without giving effect to any choice or conflict of law provision or rule.
                        </p>
                        <p>
                            Any legal suit, action, or proceeding arising out of, or related to, these Terms of Use or the Service shall be instituted exclusively in the federal courts of the United States or the courts of the Commonwealth of Massachusetts. You waive any and all objections to the exercise of jurisdiction over you by such courts and to venue in such courts.
                        </p>

                        <h2>15. Miscellaneous</h2>
                        <p>
                            a.  <strong>Entire Agreement:</strong> These Terms and our Privacy Policy constitute the sole and entire agreement between you and AlcivarTech LLC regarding the Service and supersede all prior and contemporaneous understandings, agreements, representations, and warranties, both written and oral, regarding the Service.
                        </p>
                        <p>
                            b.  <strong>Waiver and Severability:</strong> No waiver by AlcivarTech LLC of any term or condition set out in these Terms shall be deemed a further or continuing waiver of such term or condition or a waiver of any other term or condition, and any failure of AlcivarTech LLC to assert a right or provision under these Terms shall not constitute a waiver of such right or provision. If any provision of these Terms is held by a court or other tribunal of competent jurisdiction to be invalid, illegal, or unenforceable for any reason, such provision shall be eliminated or limited to the minimum extent such that the remaining provisions of the Terms will continue in full force and effect.
                        </p>
                        <p>
                            c.  <strong>Assignment:</strong> You may not assign or transfer these Terms, by operation of law or otherwise, without AlcivarTech LLC’s prior written consent. Any attempt by you to assign or transfer these Terms, without such consent, will be null. AlcivarTech LLC may freely assign or transfer these Terms without restriction. Subject to the foregoing, these Terms will bind and inure to the benefit of the parties, their successors, and permitted assigns.
                        </p>
                        <p>
                            d.  <strong>Notices:</strong> Any notices or other communications provided by AlcivarTech LLC under these Terms, including those regarding modifications to these Terms, will be given: (i) via email; or (ii) by posting to the Service. For notices made by e-mail, the date of receipt will be deemed the date on which such notice is transmitted.
                        </p>

                        <h2>16. Contact Information</h2>
                        <p>
                            If you have any questions about these Terms, please contact us at:
                            <br>
                            AlcivarTech LLC
                            <br>
                            Email: <a href="mailto:help@bostonscope.com" class="text-blue-600 hover:text-blue-800 underline">help@bostonscope.com</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>

<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const getFormattedDate = () => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date().toLocaleDateString(undefined, options);
};

const effectiveDate = ref(getFormattedDate());

</script>

<style scoped>
/* Custom prose styles if needed, Tailwind's @tailwindcss/typography plugin handles a lot. */
.prose h2 {
    @apply text-2xl font-semibold text-gray-800 mt-8 mb-3 border-b pb-2;
}
.prose h3 {
     @apply text-xl font-semibold text-gray-700 mt-6 mb-2;
}
.prose p {
    @apply mb-4 leading-relaxed;
}
.prose ul {
    @apply list-disc pl-6 mb-4 space-y-1;
}
.prose strong {
    @apply font-semibold;
}
.prose .lead {
    @apply text-lg text-gray-700;
}
</style>