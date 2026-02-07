/**
 * Blog Post Interactions Module
 * Handles collapse/expand of blog post views and list item interactions
 */

export function setupBlogPostCollapse(root = document) {
    const collapseButtons = root.querySelectorAll('.blog-collapse');

    collapseButtons.forEach((closeBtn) => {
        if (closeBtn.dataset.collapseListenerBound === 'true') {
            return;
        }
        closeBtn.dataset.collapseListenerBound = 'true';

        closeBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            // Get the view frame
            const viewFrame = closeBtn.closest('turbo-frame[data-view-frame]');
            if (!viewFrame) {
                return;
            }

            // Find the parent blog-post frame by traversing up from view frame
            let parentFrame = viewFrame.parentElement;
            while (parentFrame && !parentFrame.id.startsWith('blog-post-')) {
                parentFrame = parentFrame.parentElement;
            }

            if (parentFrame && parentFrame.id.startsWith('blog-post-')) {
                // Reset the parent frame state
                parentFrame.removeAttribute('data-expanded');
                parentFrame.classList.remove('blog-post-expanded');

                const featured = parentFrame.querySelector('.blog-featured');
                if (featured) featured.style.display = '';

                const listItem = parentFrame.querySelector('.blog-list-item');
                if (listItem) listItem.style.display = '';
            }

            // Clear the view frame content
            viewFrame.innerHTML = '';
        });
    });
}

export function markBlogPostExpanded(root = document) {
    const postView = root.querySelector('.blog-post-view[data-blog-post]');
    if (!postView) {
        return;
    }

    const slug = postView.dataset.blogPost;
    if (!slug) {
        return;
    }

    // Find the containing blog-post frame - look in the document, not just root
    const containerFrame = document.getElementById('blog-post-' + slug);
    if (!containerFrame) {
        return;
    }

    containerFrame.setAttribute('data-expanded', 'true');
    containerFrame.classList.add('blog-post-expanded');

    const featured = containerFrame.querySelector('.blog-featured');
    if (featured) featured.style.display = 'none';

    const listItem = containerFrame.querySelector('.blog-list-item');
    if (listItem) listItem.style.display = 'none';
}

export default function initBlogInteractions(options = {}) {
    const root = options.root || document;

    setupBlogPostCollapse(root);
    markBlogPostExpanded(root);
}
