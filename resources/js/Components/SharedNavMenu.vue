<template>
    <nav class="bg-white border-b border-gray-100 w-full">
        <!-- Primary Navigation Menu -->
// ...existing code...
        <!-- Responsive Navigation Menu -->
        <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
            <div class="pt-2 pb-3 space-y-1">
                <ResponsiveNavLink :href="route('map.index')" :active="route().current('map.index')"> Home </ResponsiveNavLink>
                <ResponsiveNavLink :href="route('crime-map')" :active="route().current('crime-map')"> Boston Crime Map </ResponsiveNavLink>
                <ResponsiveNavLink :href="route('cases.indexnofilter')" :active="route().current('cases.indexnofilter') || route().current('scatter')"> Boston 311 Case Map </ResponsiveNavLink>
                <ResponsiveNavLink :href="route('subscription.page')" :active="route().current('subscription.page')"> Subscription </ResponsiveNavLink>
            </div>

            <!-- Responsive Settings Options -->
// ...existing code...
            
            <!-- Footer Links for Responsive -->
            <div class="pt-4 pb-1 border-t border-gray-200">
                 <div class="mt-3 space-y-1">
                    <ResponsiveNavLink :href="route('about.us')" :active="route().current('about.us')"> About Us </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('help.contact')" :active="route().current('help.contact')"> Help/Contact </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('privacy.policy')" :active="route().current('privacy.policy')"> Privacy Policy </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('terms.of.use')" :active="route().current('terms.of.use')"> Terms of Use </ResponsiveNavLink>
                 </div>
            </div>
        </div>

        <!-- Static Footer Links for Desktop (Optional: can be styled differently or placed in a dedicated footer component) -->
        <!-- This section is removed as it's now handled by Footer.vue via PageTemplate.vue -->
    </nav>
</template>

<script setup>
// ...existing code...
</script>
