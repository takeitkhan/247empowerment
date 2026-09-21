/**
 * Complete Multi-Journey Expert Hiring Wizard
 * Includes all 24 services and 23 journey types with conditional questions
 */

(function() {
    'use strict';

    // ============================================
    // CONFIGURATION DATA
    // ============================================

    const GOAL_OPTIONS = [
        'Website & App Development',
        'Marketing & SEO',
        'Design & Branding',
        'Video & Content Production',
        'AI & Automation',
        'Ecommerce',
        'Payments & Online Checkout',
        'Marketing & Outreach',
        'Healthcare & Medical',
        'Technology & Software',
        'Game Development',
        'Advanced Technology',
        'Cybersecurity & IT',
        'Sales & Business Growth',
        'Coaching & Team Training',
        'Marketing Creative & Business Growth',
        'AI Help & Coaching',
        'Ministry & Spirituality',
        'Nonprofits & Fundraising',
        'Business & Entrepreneurship',
        'Legal & Business Protection',
        'Money & Financial Tools',
        'Health & Wellness',
        'Something else / not sure'
    ];

    const GOAL_TO_JOURNEY = {
        'Website & App Development': 'website-app-development',
        'Marketing & SEO': 'marketing-seo',
        'Design & Branding': 'design-branding',
        'Video & Content Production': 'video-content-production',
        'AI & Automation': 'ai-automation',
        'Ecommerce': 'ecommerce',
        'Payments & Online Checkout': 'payments-checkout',
        'Marketing & Outreach': 'marketing-outreach',
        'Healthcare & Medical': 'healthcare-medical',
        'Technology & Software': 'technology',
        'Game Development': 'game-development',
        'Advanced Technology': 'advanced-technology',
        'Cybersecurity & IT': 'cybersecurity-it',
        'Sales & Business Growth': 'sales-business-growth',
        'Coaching & Team Training': 'coaching-team-training',
        'Marketing Creative & Business Growth': 'marketing-growth',
        'AI Help & Coaching': 'ai-coaching',
        'Ministry & Spirituality': 'ministry',
        'Nonprofits & Fundraising': 'nonprofits',
        'Business & Entrepreneurship': 'business',
        'Legal & Business Protection': 'legal',
        'Money & Financial Tools': 'money',
        'Health & Wellness': 'health',
        'Something else / not sure': 'not-sure'
    };

    // ============================================
    // JOURNEY DEFINITIONS (All 23 journeys + not-sure)
    // ============================================

    const JOURNEYS = {
        'website-app-development': {
            label: 'Website & App Development',
            blocks: [
                { key: 'project_type', type: 'single', title: 'What type of project do you need?', options: ['Website', 'Mobile App', 'Web Application', 'Desktop Application', 'Not sure yet'] },
                { key: 'current_status', type: 'single', title: 'What\'s your current status?', options: ['Have a detailed concept/design', 'Have a rough idea', 'Need help with the concept', 'Already have some code'] },
                { key: 'tech_stack', type: 'single', title: 'Do you have a technology preference?', options: ['React/JavaScript', 'Vue/Node.js', 'Python/Django', 'PHP/Laravel', 'No preference', 'Need recommendation'] },
                { key: 'timeline', type: 'single', title: 'Timeline expectations?', options: ['ASAP (1-3 months)', 'Normal (3-6 months)', 'Flexible', 'Not sure yet'] },
                { key: 'key_features', type: 'text', title: 'What are the key features you need?', placeholder: 'e.g., user authentication, payment processing, real-time updates' }
            ]
        },
        'marketing-seo': {
            label: 'Marketing & SEO',
            blocks: [
                { key: 'marketing_focus', type: 'multi', title: 'What marketing areas interest you?', options: ['SEO/Search Optimization', 'Content Marketing', 'Email Marketing', 'Social Media Strategy', 'PPC Advertising', 'Lead Generation', 'All of the above'] },
                { key: 'current_reach', type: 'single', title: 'What\'s your current reach?', options: ['Just starting (0-100 followers)', 'Small audience (100-1K)', 'Growing (1K-10K)', 'Established (10K+)', 'Not sure'] },
                { key: 'competitive_market', type: 'single', title: 'How competitive is your market?', options: ['Low competition', 'Moderate competition', 'Highly competitive', 'Not sure'] },
                { key: 'content_style', type: 'single', title: 'Your preferred content style?', options: ['Blog posts', 'Videos', 'Podcasts', 'Infographics', 'Mix of everything', 'Not sure'] },
                { key: 'seo_goals', type: 'text', title: 'What are your main SEO/marketing goals?', placeholder: 'e.g., rank for specific keywords, increase traffic, boost sales' }
            ]
        },
        'design-branding': {
            label: 'Design & Branding',
            blocks: [
                { key: 'design_needs', type: 'multi', title: 'What design services do you need?', options: ['Logo & Brand Identity', 'Website Design', 'App/UI Design', 'Print Design', 'Packaging', 'Social Media Assets', 'Complete rebranding'] },
                { key: 'brand_stage', type: 'single', title: 'Where\'s your brand at?', options: ['Completely new brand', 'Existing brand needing refresh', 'Multiple brands to manage', 'Just the logo/identity'] },
                { key: 'brand_personality', type: 'single', title: 'What\'s your brand personality?', options: ['Professional & Corporate', 'Creative & Artistic', 'Playful & Fun', 'Bold & Edgy', 'Luxury & Premium', 'Not sure yet'] },
                { key: 'design_style', type: 'single', title: 'Preferred design style?', options: ['Modern & Minimalist', 'Bold & Colorful', 'Luxury & Elegant', 'Playful & Quirky', 'Traditional', 'No preference'] },
                { key: 'brand_story', type: 'textarea', title: 'Tell us your brand story', placeholder: 'What\'s unique about your brand? What\'s your mission?' }
            ]
        },
        'video-content-production': {
            label: 'Video & Content Production',
            blocks: [
                { key: 'video_type', type: 'multi', title: 'What type of video content?', options: ['Product Demo', 'Testimonial/Case Study', 'Explainer Video', 'Animation', 'Commercial/Promo', 'Training/Tutorial', 'Live Streaming'] },
                { key: 'video_purpose', type: 'single', title: 'Primary purpose?', options: ['Marketing/Promotion', 'Education/Training', 'Entertainment', 'Documentation', 'Internal Communication'] },
                { key: 'production_scale', type: 'single', title: 'Production scale?', options: ['Simple (1-2 cameras)', 'Medium (multi-camera)', 'Complex (special effects, drones, etc.)'] },
                { key: 'existing_content', type: 'single', title: 'Do you have existing footage?', options: ['Yes, lots', 'Some', 'Starting from scratch', 'Mix of both'] },
                { key: 'video_message', type: 'textarea', title: 'What\'s the core message or story?', placeholder: 'Describe what you want the video to communicate' }
            ]
        },
        'ai-automation': {
            label: 'AI & Automation',
            blocks: [
                { key: 'ai_use_case', type: 'multi', title: 'What AI/automation challenges do you have?', options: ['Customer Service Chatbot', 'Data Analysis', 'Content Generation', 'Process Automation', 'Predictive Analytics', 'Image/Video Processing', 'Custom AI Model'] },
                { key: 'current_tools', type: 'single', title: 'Are you currently using any AI tools?', options: ['No, starting fresh', 'Using some (ChatGPT, etc.)', 'Already have custom solutions', 'Mix of tools'] },
                { key: 'data_volume', type: 'single', title: 'How much data are we working with?', options: ['Small (< 1GB)', 'Medium (1GB-100GB)', 'Large (> 100GB)', 'Not sure'] },
                { key: 'integration_needs', type: 'single', title: 'Need integration with existing systems?', options: ['Yes, CRM', 'Yes, ERP/inventory', 'Yes, other', 'No, standalone', 'Not sure'] },
                { key: 'automation_goals', type: 'textarea', title: 'What specific processes need automation?', placeholder: 'List the repetitive tasks you want to automate' }
            ]
        },
        'ecommerce': {
            label: 'Ecommerce',
            blocks: [
                { key: 'ecommerce_platform', type: 'single', title: 'Preferred platform?', options: ['Shopify', 'WooCommerce', 'Magento', 'Custom build', 'Migrate from existing', 'Not sure'] },
                { key: 'product_type', type: 'multi', title: 'What do you sell?', options: ['Physical Products', 'Digital Products', 'Services', 'Subscriptions', 'Mix of above'] },
                { key: 'product_count', type: 'single', title: 'Approximate product count?', options: ['< 50', '50-500', '500-5000', '5000+', 'Not sure yet'] },
                { key: 'sales_channels', type: 'single', title: 'How many sales channels?', options: ['Just website', 'Website + marketplace (Amazon, eBay)', 'Website + social media', 'Multiple channels', 'Starting out'] },
                { key: 'business_goals', type: 'textarea', title: 'What are your ecommerce goals?', placeholder: 'Revenue targets, growth strategy, customer experience goals' }
            ]
        },
        'payments-checkout': {
            label: 'Payments & Online Checkout',
            blocks: [
                { key: 'payment_processors', type: 'multi', title: 'Which payment methods?', options: ['Credit/Debit Cards', 'PayPal', 'Stripe', 'Apple Pay/Google Pay', 'Cryptocurrency', 'Bank Transfer', 'All major options'] },
                { key: 'current_system', type: 'single', title: 'Do you have a payment system?', options: ['Yes, working fine', 'Yes, needs improvement', 'No, need to set up', 'Have issues with current system'] },
                { key: 'transaction_volume', type: 'single', title: 'Expected transaction volume?', options: ['Low (< 100/month)', 'Medium (100-1K/month)', 'High (1K-10K/month)', 'Very High (10K+/month)'] },
                { key: 'geographic_reach', type: 'single', title: 'Geographic reach?', options: ['US only', 'North America', 'Europe', 'Global', 'Multiple specific regions'] },
                { key: 'checkout_concerns', type: 'textarea', title: 'Any specific checkout concerns?', placeholder: 'e.g., high cart abandonment, international payments, compliance' }
            ]
        },
        'marketing-outreach': {
            label: 'Marketing & Outreach',
            blocks: [
                { key: 'outreach_channels', type: 'multi', title: 'Preferred outreach channels?', options: ['Email Campaigns', 'Social Media', 'LinkedIn Networking', 'Cold Calling', 'Partnership Building', 'Events & Webinars'] },
                { key: 'target_audience', type: 'single', title: 'Who\'s your target audience?', options: ['B2C (Consumers)', 'B2B (Businesses)', 'B2B2C (Both)', 'Specific niche', 'Varied audiences'] },
                { key: 'audience_size', type: 'single', title: 'Target audience size?', options: ['Very small (< 1K)', 'Small (1K-10K)', 'Medium (10K-100K)', 'Large (100K+)', 'Unsure'] },
                { key: 'budget_allocation', type: 'single', title: 'How to allocate budget?', options: ['Mostly digital ads', 'Content & organic', 'Sales team & outreach', 'Even mix', 'Not sure'] },
                { key: 'outreach_goals', type: 'textarea', title: 'What outreach goals?', placeholder: 'Lead generation targets, customer acquisition, brand awareness' }
            ]
        },
        'healthcare-medical': {
            label: 'Healthcare & Medical',
            blocks: [
                { key: 'healthcare_focus', type: 'multi', title: 'Healthcare focus areas?', options: ['Telemedicine Platform', 'Patient Management System', 'Medical Records', 'Appointment Scheduling', 'Health Education', 'Compliance & Security', 'Other'] },
                { key: 'compliance_level', type: 'single', title: 'Compliance requirements?', options: ['HIPAA (US)', 'GDPR (EU)', 'Both HIPAA & GDPR', 'Local regulations', 'Not sure'] },
                { key: 'current_systems', type: 'single', title: 'Do you have existing systems?', options: ['Yes, need integration', 'Yes, need replacement', 'Starting from scratch', 'Partial systems'] },
                { key: 'patient_count', type: 'single', title: 'Approximate patient count?', options: ['< 1000', '1K-10K', '10K-100K', '100K+', 'Unsure'] },
                { key: 'healthcare_requirements', type: 'textarea', title: 'Specific requirements?', placeholder: 'Features, integrations, security concerns' }
            ]
        },
        'technology': {
            label: 'Technology & Software',
            blocks: [
                { key: 'software_type', type: 'multi', title: 'Type of software?', options: ['Enterprise Software', 'SaaS Platform', 'Mobile App', 'Cloud Solution', 'Legacy Modernization', 'Custom Tool', 'API Development'] },
                { key: 'industry', type: 'single', title: 'What industry?', options: ['Finance', 'Healthcare', 'Retail', 'Manufacturing', 'Education', 'Government', 'Other'] },
                { key: 'team_size', type: 'single', title: 'Expected user base?', options: ['Small (< 100)', 'Medium (100-1K)', 'Large (1K-10K)', 'Enterprise (10K+)', 'Unsure'] },
                { key: 'existing_infrastructure', type: 'single', title: 'Existing infrastructure?', options: ['On-premise', 'Cloud-based', 'Hybrid', 'Need to set up'] },
                { key: 'tech_requirements', type: 'textarea', title: 'Technical requirements?', placeholder: 'Must-have features, integrations, performance needs' }
            ]
        },
        'game-development': {
            label: 'Game Development',
            blocks: [
                { key: 'game_type', type: 'single', title: 'Type of game?', options: ['Mobile Game', 'Console Game', 'PC Game', 'Web Game', 'Multiplayer Online', 'VR/AR Game'] },
                { key: 'game_genre', type: 'single', title: 'Game genre?', options: ['Action', 'Puzzle', 'RPG', 'Strategy', 'Simulation', 'Educational', 'Other'] },
                { key: 'game_engine', type: 'single', title: 'Preferred engine?', options: ['Unity', 'Unreal Engine', 'Godot', 'Custom', 'No preference'] },
                { key: 'target_platforms', type: 'multi', title: 'Target platforms?', options: ['iOS', 'Android', 'Windows', 'Mac', 'Linux', 'Console'] },
                { key: 'game_scope', type: 'textarea', title: 'Game concept & features?', placeholder: 'Description, key features, story, gameplay mechanics' }
            ]
        },
        'advanced-technology': {
            label: 'Advanced Technology',
            blocks: [
                { key: 'advanced_tech', type: 'multi', title: 'Advanced tech interests?', options: ['Machine Learning', 'Blockchain', 'IoT', 'Quantum Computing', '5G', 'Edge Computing', 'Other'] },
                { key: 'research_vs_production', type: 'single', title: 'Research or production?', options: ['Research/Proof of Concept', 'Production implementation', 'Both', 'Not sure'] },
                { key: 'data_infrastructure', type: 'single', title: 'Data infrastructure needs?', options: ['Big Data processing', 'Real-time analytics', 'Data warehousing', 'Not sure', 'Don\'t need'] },
                { key: 'risk_tolerance', type: 'single', title: 'Risk tolerance?', options: ['Conservative (proven tech)', 'Moderate (some experimentation)', 'Aggressive (cutting edge)'] },
                { key: 'tech_vision', type: 'textarea', title: 'Your tech vision?', placeholder: 'Long-term goals, innovation direction, competitive advantage' }
            ]
        },
        'cybersecurity-it': {
            label: 'Cybersecurity & IT',
            blocks: [
                { key: 'security_needs', type: 'multi', title: 'Security concerns?', options: ['Data Protection', 'Network Security', 'Cloud Security', 'Identity Management', 'Threat Detection', 'Compliance/Audit', 'Incident Response'] },
                { key: 'organization_size', type: 'single', title: 'Organization size?', options: ['Small (< 50 employees)', 'Medium (50-500)', 'Large (500-5K)', 'Enterprise (5K+)'] },
                { key: 'industry_regulation', type: 'single', title: 'Industry regulations?', options: ['Heavily regulated (Finance, Healthcare)', 'Moderately regulated', 'Minimal regulation', 'Not sure'] },
                { key: 'incident_history', type: 'single', title: 'Previous security incidents?', options: ['Yes, multiple', 'Yes, one major', 'Minor issues', 'None/Not sure'] },
                { key: 'security_goals', type: 'textarea', title: 'Security goals?', placeholder: 'Compliance targets, risk mitigation, infrastructure upgrades' }
            ]
        },
        'sales-business-growth': {
            label: 'Sales & Business Growth',
            blocks: [
                { key: 'growth_focus', type: 'multi', title: 'Growth focus areas?', options: ['Lead Generation', 'Sales Process Optimization', 'Team Training', 'Market Expansion', 'Revenue Optimization', 'Customer Retention'] },
                { key: 'current_revenue', type: 'single', title: 'Current revenue stage?', options: ['Pre-revenue/Startup', '< $100K annual', '$100K-$1M', '$1M-$10M', '$10M+'] },
                { key: 'growth_target', type: 'single', title: '1-year growth target?', options: ['Modest (10-25%)', 'Aggressive (50%+)', 'Scaling (100%+)', 'Unsure', 'Just starting'] },
                { key: 'sales_channels', type: 'single', title: 'Current sales channels?', options: ['Direct sales only', 'Sales + online', 'Multiple channels', 'Starting out'] },
                { key: 'growth_challenges', type: 'textarea', title: 'Main growth challenges?', placeholder: 'What\'s holding you back? Lead quality? Sales team capacity?' }
            ]
        },
        'coaching-team-training': {
            label: 'Coaching & Team Training',
            blocks: [
                { key: 'training_focus', type: 'multi', title: 'Training focus areas?', options: ['Leadership Development', 'Sales Skills', 'Technical Skills', 'Soft Skills', 'Team Building', 'Executive Coaching', 'Custom Training'] },
                { key: 'training_format', type: 'single', title: 'Preferred training format?', options: ['In-person workshops', 'Online/Virtual', 'Hybrid', 'Self-paced content', 'One-on-one coaching'] },
                { key: 'team_size', type: 'single', title: 'Team/group size?', options: ['Individual (1)', 'Small team (2-10)', 'Department (10-50)', 'Organization (50+)'] },
                { key: 'training_outcomes', type: 'single', title: 'Desired outcomes?', options: ['Skill improvement', 'Behavioral change', 'Team cohesion', 'Performance metrics', 'All of above'] },
                { key: 'training_goals', type: 'textarea', title: 'Specific training goals?', placeholder: 'What skills/behaviors do you want to develop?' }
            ]
        },
        'marketing-growth': {
            label: 'Marketing Creative & Business Growth',
            blocks: [
                { key: 'creative_services', type: 'multi', title: 'Creative services needed?', options: ['Copywriting', 'Creative Strategy', 'Brand Storytelling', 'Campaign Development', 'Content Creation', 'Social Media Strategy'] },
                { key: 'creative_vision', type: 'single', title: 'Your creative vision?', options: ['Completely original', 'Inspired by industry leaders', 'Trendy/Current', 'Timeless/Classic', 'Not sure yet'] },
                { key: 'marketing_budget', type: 'single', title: 'Marketing budget scope?', options: ['Bootstrap/minimal', 'Modest budget', 'Comfortable budget', 'Large budget', 'Not determined'] },
                { key: 'campaign_frequency', type: 'single', title: 'Campaign frequency?', options: ['One-time campaign', 'Ongoing (quarterly)', 'Monthly campaigns', 'Weekly/continuous'] },
                { key: 'growth_vision', type: 'textarea', title: 'Growth vision?', placeholder: 'How do you want your brand to grow? What\'s success?' }
            ]
        },
        'ai-coaching': {
            label: 'AI Help & Coaching',
            blocks: [
                { key: 'ai_coaching_focus', type: 'multi', title: 'AI coaching focus?', options: ['Business Strategy', 'Marketing Strategy', 'Personal Development', 'Career Transition', 'Decision Making', 'Problem Solving'] },
                { key: 'coaching_intensity', type: 'single', title: 'Coaching intensity?', options: ['Light (1-2 sessions/month)', 'Moderate (weekly)', 'Intensive (multiple/week)', 'Ongoing support', 'As-needed'] },
                { key: 'current_challenges', type: 'single', title: 'Main challenges now?', options: ['Strategic clarity', 'Execution issues', 'Mindset/confidence', 'Team dynamics', 'Market positioning'] },
                { key: 'coaching_timeline', type: 'single', title: 'Coaching timeline?', options: ['Short-term (3 months)', 'Medium-term (6 months)', 'Long-term (12+ months)', 'Flexible'] },
                { key: 'coaching_goals', type: 'textarea', title: 'Coaching goals?', placeholder: 'What specific outcomes do you want to achieve?' }
            ]
        },
        'ministry': {
            label: 'Ministry & Spirituality',
            blocks: [
                { key: 'ministry_focus', type: 'multi', title: 'Ministry focus?', options: ['Community Outreach', 'Digital Ministry', 'Leadership Development', 'Content Creation', 'Event Management', 'Fundraising'] },
                { key: 'organization_type', type: 'single', title: 'Organization type?', options: ['Church', 'Nonprofit Ministry', 'Faith-based Business', 'Spiritual Center', 'Other'] },
                { key: 'congregation_size', type: 'single', title: 'Congregation/community size?', options: ['Small (< 100)', 'Medium (100-500)', 'Large (500-2K)', 'Very Large (2K+)'] },
                { key: 'digital_presence', type: 'single', title: 'Digital presence needs?', options: ['Website', 'Live streaming', 'Member portal', 'All of above', 'Minimal'] },
                { key: 'ministry_vision', type: 'textarea', title: 'Ministry vision?', placeholder: 'How do you want to serve and grow your community?' }
            ]
        },
        'nonprofits': {
            label: 'Nonprofits & Fundraising',
            blocks: [
                { key: 'nonprofit_focus', type: 'multi', title: 'Nonprofit focus?', options: ['Fundraising Strategy', 'Donor Management', 'Impact Measurement', 'Grant Writing', 'Campaign Strategy', 'Volunteer Coordination'] },
                { key: 'fundraising_goal', type: 'single', title: 'Annual fundraising goal?', options: ['< $50K', '$50K-$250K', '$250K-$1M', '$1M+', 'Not sure yet'] },
                { key: 'donor_base', type: 'single', title: 'Current donor base size?', options: ['< 50 donors', '50-200 donors', '200-1K donors', '1K+ donors', 'Starting out'] },
                { key: 'fund_sources', type: 'multi', title: 'Fund sources?', options: ['Individual Donations', 'Grants', 'Corporate Sponsorships', 'Events', 'Planned Giving'] },
                { key: 'nonprofit_mission', type: 'textarea', title: 'Your mission & impact?', placeholder: 'What impact do you want to create? What\'s your mission?' }
            ]
        },
        'business': {
            label: 'Business & Entrepreneurship',
            blocks: [
                { key: 'business_stage', type: 'single', title: 'Business stage?', options: ['Idea stage', 'Startup (< 1 year)', 'Growth stage (1-3 years)', 'Established (3+ years)', 'Scaling/Expansion'] },
                { key: 'business_needs', type: 'multi', title: 'Business support needed?', options: ['Business Planning', 'Market Research', 'Financial Management', 'Operations', 'Compliance/Legal', 'Strategic Planning'] },
                { key: 'team_structure', type: 'single', title: 'Current team?', options: ['Solo founder', 'Small team (2-5)', 'Growing team (5-20)', 'Larger team (20+)', 'Contractors/Virtual'] },
                { key: 'scaling_ambitions', type: 'single', title: 'Scaling ambitions?', options: ['Modest growth', 'Regional expansion', 'National scale', 'International', 'Exit/acquisition'] },
                { key: 'business_vision', type: 'textarea', title: 'Business vision?', placeholder: 'Where do you want to take this business? 5-year goals?' }
            ]
        },
        'legal': {
            label: 'Legal & Business Protection',
            blocks: [
                { key: 'legal_needs', type: 'multi', title: 'Legal support needed?', options: ['Business Formation', 'Contracts', 'IP Protection', 'Compliance', 'Employment Law', 'Dispute Resolution'] },
                { key: 'organization_structure', type: 'single', title: 'Business structure?', options: ['Sole Proprietor', 'Partnership', 'LLC', 'Corporation', 'Not sure'] },
                { key: 'legal_urgency', type: 'single', title: 'Urgency level?', options: ['ASAP (1-2 weeks)', 'Soon (1 month)', 'Standard (1-3 months)', 'Planning ahead'] },
                { key: 'ip_concerns', type: 'single', title: 'IP protection concerns?', options: ['Yes, critical', 'Some concerns', 'Not relevant', 'Not sure'] },
                { key: 'legal_goals', type: 'textarea', title: 'Legal/protection goals?', placeholder: 'What protection/compliance matters most to you?' }
            ]
        },
        'money': {
            label: 'Money & Financial Tools',
            blocks: [
                { key: 'financial_focus', type: 'multi', title: 'Financial focus?', options: ['Bookkeeping', 'Tax Planning', 'Accounting', 'Financial Forecasting', 'Investment Strategy', 'Wealth Management'] },
                { key: 'financial_stage', type: 'single', title: 'Financial stage?', options: ['Just getting organized', 'Growing complexity', 'Significant assets', 'Complex financial picture'] },
                { key: 'team_situation', type: 'single', title: 'Team situation?', options: ['Personal finances', 'Business finances', 'Business + personal', 'Managing others\' finances'] },
                { key: 'tools_needed', type: 'single', title: 'Tools/software needed?', options: ['Accounting software', 'Financial planning tools', 'Tax software', 'Dashboard/reporting', 'Complete suite'] },
                { key: 'financial_goals', type: 'textarea', title: 'Financial goals?', placeholder: 'Revenue targets, profitability, wealth building goals' }
            ]
        },
        'health': {
            label: 'Health & Wellness',
            blocks: [
                { key: 'wellness_focus', type: 'multi', title: 'Wellness focus?', options: ['Fitness Coaching', 'Nutrition', 'Mental Health', 'Lifestyle Change', 'Preventive Health', 'Holistic Wellness'] },
                { key: 'wellness_stage', type: 'single', title: 'Your wellness stage?', options: ['Starting fresh', 'Improving existing habits', 'Addressing specific issues', 'Optimizing performance'] },
                { key: 'health_concerns', type: 'single', title: 'Current health concerns?', options: ['Weight management', 'Energy/stamina', 'Stress/anxiety', 'Chronic conditions', 'General wellness'] },
                { key: 'commitment_level', type: 'single', title: 'Commitment level?', options: ['Casual/exploratory', 'Moderate commitment', 'Serious/dedicated', 'Transformational'] },
                { key: 'wellness_goals', type: 'textarea', title: 'Wellness goals?', placeholder: 'What health outcomes do you want to achieve?' }
            ]
        },
        'not-sure': {
            label: 'Something else / not sure',
            blocks: [
                { key: 'describe_need', type: 'textarea', title: 'Tell us what you need', placeholder: 'Describe your project, challenge, or what you\'re looking for' },
                { key: 'industry_or_field', type: 'text', title: 'Industry or field?', placeholder: 'e.g., Manufacturing, Education, Real Estate' },
                { key: 'any_specifics', type: 'textarea', title: 'Any other specifics?', placeholder: 'Budget, timeline, team size, or any other details' }
            ]
        }
    };

    // ============================================
    // STATE MANAGEMENT
    // ============================================

    const state = {
        stepIndex: 0,
        goals: [],
        journeyAnswers: {},
        globalAnswers: { budget: '', timeline: '', combinedNotes: '' },
        contact: {
            fullName: window.userFormData?.name || '',
            projectName: '',
            company: '',
            country: '',
            email: window.userFormData?.email || '',
            phone: '',
            preferredContact: 'email',
            availability: '',
            notes: ''
        },
        files: []
    };

    // ============================================
    // UI RENDERING
    // ============================================

    function render() {
        const root = document.getElementById('wizard-root');
        if (!root) return;

        root.innerHTML = getStepHTML();
        attachEventListeners();
    }

    function getStepHTML() {
        const totalSteps = 4 + state.goals.length;
        const progress = ((state.stepIndex + 1) / totalSteps) * 100;

        let html = `
            <div class="wizard-card">
                <div class="wizard-progress"><span style="width:${progress}%"></span></div>
        `;

        // Step 0: Select Services/Goals
        if (state.stepIndex === 0) {
            html += `
                <p class="wizard-block-tag">Step 1 of ${totalSteps}</p>
                <h3>What services do you need?</h3>
                <p class="sub">Select one or more services that best fit your project.</p>
                <div class="wizard-options" id="goalOptions">
            `;
            GOAL_OPTIONS.forEach(goal => {
                const isSelected = state.goals.includes(goal);
                html += `
                    <div class="wizard-option ${isSelected ? 'selected' : ''}" data-goal="${goal}">
                        ${goal}
                        ${isSelected ? '<span class="wizard-check">✓</span>' : ''}
                    </div>
                `;
            });
            html += `</div>`;
        }
        // Steps 1 to state.goals.length: Journey-specific questions
        else if (state.stepIndex <= state.goals.length) {
            const goalIndex = state.stepIndex - 1;
            const goal = state.goals[goalIndex];
            const journeySlug = GOAL_TO_JOURNEY[goal];
            const journey = JOURNEYS[journeySlug];

            if (!journey) {
                html += `<p>Journey not found for ${goal}</p>`;
            } else {
                html += `
                    <p class="wizard-block-tag">Step ${state.stepIndex + 1} of ${totalSteps}: ${goal}</p>
                    <h3>${journey.label}</h3>
                    <p class="sub">Tell us about your ${journey.label.toLowerCase()} needs.</p>
                    <div id="journeyBlocks">
                `;

                if (!state.journeyAnswers[journeySlug]) {
                    state.journeyAnswers[journeySlug] = {};
                }

                journey.blocks.forEach((block, blockIndex) => {
                    html += renderBlock(block, journeySlug, blockIndex);
                });

                html += `</div>`;
            }
        }
        // Final steps: Contact info and global questions
        else if (state.stepIndex === state.goals.length + 1) {
            // Contact information
            html += `
                <p class="wizard-block-tag">Step ${state.stepIndex + 1} of ${totalSteps}</p>
                <h3>Your Contact Information</h3>
                <p class="sub">How should we reach you?</p>
                <div id="contactFields">
                    <div class="wizard-field">
                        <label>Full Name *</label>
                        <input type="text" name="fullName" value="${state.contact.fullName}" placeholder="Your full name" required>
                    </div>
                    <div class="wizard-field">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="${state.contact.email}" placeholder="your@email.com" required>
                    </div>
                    <div class="wizard-field">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="${state.contact.phone}" placeholder="(123) 456-7890">
                    </div>
                    <div class="wizard-field">
                        <label>Project Name *</label>
                        <input type="text" name="projectName" value="${state.contact.projectName}" placeholder="Your project name" required>
                    </div>
                    <div class="wizard-field">
                        <label>Company</label>
                        <input type="text" name="company" value="${state.contact.company}" placeholder="Your company (optional)">
                    </div>
                    <div class="wizard-field">
                        <label>Country</label>
                        <input type="text" name="country" value="${state.contact.country}" placeholder="Your country (optional)">
                    </div>
                    <div class="wizard-field">
                        <label>Preferred Contact Method</label>
                        <select name="preferredContact" style="appearance:none; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:11px 12px; width:100%; color:var(--ink); font-family:inherit;">
                            <option value="email" ${state.contact.preferredContact === 'email' ? 'selected' : ''}>Email</option>
                            <option value="phone" ${state.contact.preferredContact === 'phone' ? 'selected' : ''}>Phone</option>
                            <option value="either" ${state.contact.preferredContact === 'either' ? 'selected' : ''}>Either</option>
                        </select>
                    </div>
                    <div class="wizard-field">
                        <label>Availability</label>
                        <input type="text" name="availability" value="${state.contact.availability}" placeholder="e.g., Available in 2 weeks">
                    </div>
                </div>
            `;
        }
        else if (state.stepIndex === state.goals.length + 2) {
            // Budget, timeline, and global notes
            html += `
                <p class="wizard-block-tag">Step ${state.stepIndex + 1} of ${totalSteps}</p>
                <h3>Project Scope</h3>
                <p class="sub">Tell us about your budget and timeline.</p>
                <div id="globalFields">
                    <div class="wizard-field">
                        <label>Budget Range</label>
                        <select name="budget" style="appearance:none; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:11px 12px; width:100%; color:var(--ink); font-family:inherit;">
                            <option value="">Select a budget range</option>
                            <option value="under-5k" ${state.globalAnswers.budget === 'under-5k' ? 'selected' : ''}>Under $5,000</option>
                            <option value="5-10k" ${state.globalAnswers.budget === '5-10k' ? 'selected' : ''}>$5,000 - $10,000</option>
                            <option value="10-25k" ${state.globalAnswers.budget === '10-25k' ? 'selected' : ''}>$10,000 - $25,000</option>
                            <option value="25-50k" ${state.globalAnswers.budget === '25-50k' ? 'selected' : ''}>$25,000 - $50,000</option>
                            <option value="50-100k" ${state.globalAnswers.budget === '50-100k' ? 'selected' : ''}>$50,000 - $100,000</option>
                            <option value="100k-plus" ${state.globalAnswers.budget === '100k-plus' ? 'selected' : ''}>$100,000+</option>
                            <option value="flexible" ${state.globalAnswers.budget === 'flexible' ? 'selected' : ''}>Flexible/Not sure</option>
                        </select>
                    </div>
                    <div class="wizard-field">
                        <label>Timeline</label>
                        <select name="timeline" style="appearance:none; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:11px 12px; width:100%; color:var(--ink); font-family:inherit;">
                            <option value="">Select a timeline</option>
                            <option value="asap" ${state.globalAnswers.timeline === 'asap' ? 'selected' : ''}>ASAP (1-4 weeks)</option>
                            <option value="1-2months" ${state.globalAnswers.timeline === '1-2months' ? 'selected' : ''}>1-2 months</option>
                            <option value="2-3months" ${state.globalAnswers.timeline === '2-3months' ? 'selected' : ''}>2-3 months</option>
                            <option value="3-6months" ${state.globalAnswers.timeline === '3-6months' ? 'selected' : ''}>3-6 months</option>
                            <option value="6plus-months" ${state.globalAnswers.timeline === '6plus-months' ? 'selected' : ''}>6+ months</option>
                            <option value="flexible" ${state.globalAnswers.timeline === 'flexible' ? 'selected' : ''}>Flexible/Not sure</option>
                        </select>
                    </div>
                    <div class="wizard-field">
                        <label>Additional Notes</label>
                        <textarea name="combinedNotes" placeholder="Any other details we should know?">${state.globalAnswers.combinedNotes}</textarea>
                    </div>
                </div>
            `;
        }
        else if (state.stepIndex === state.goals.length + 3) {
            // Review
            html += `
                <p class="wizard-block-tag">Step ${state.stepIndex + 1} of ${totalSteps}</p>
                <h3>Review Your Information</h3>
                <p class="sub">Please review your submission before sending.</p>
                <div id="reviewInfo" style="font-size:13px; line-height:1.8; color:var(--ink-soft);">
                    <strong style="color:var(--ink); display:block; margin-bottom:8px;">Services Needed:</strong>
                    ${state.goals.join(', ')}
                    <hr style="border:none; border-top:1px solid var(--border); margin:16px 0;">
                    <strong style="color:var(--ink); display:block; margin-bottom:8px;">Contact:</strong>
                    ${state.contact.fullName}<br>
                    ${state.contact.email}<br>
                    ${state.contact.phone ? state.contact.phone + '<br>' : ''}
                    ${state.contact.projectName}<br>
                    <hr style="border:none; border-top:1px solid var(--border); margin:16px 0;">
                    <strong style="color:var(--ink); display:block; margin-bottom:8px;">Project Details:</strong>
                    Budget: ${state.globalAnswers.budget || 'Not specified'}<br>
                    Timeline: ${state.globalAnswers.timeline || 'Not specified'}<br>
                </div>
            `;
        }

        // Action buttons
        html += `
            <div class="wizard-actions">
                ${state.stepIndex > 0 ? '<button class="btn btn-ghost" data-action="back">← Back</button>' : ''}
                ${state.stepIndex < totalSteps - 1 ? '<button class="btn btn-primary" data-action="next">Next →</button>' : '<button class="btn btn-primary" data-action="submit">Submit Request</button>'}
            </div>
        </div>
        `;

        return html;
    }

    function renderBlock(block, journeySlug, blockIndex) {
        const value = state.journeyAnswers[journeySlug]?.[block.key] || '';
        let html = `<div class="wizard-field" data-block-key="${block.key}">`;
        html += `<label>${block.title}</label>`;

        if (block.type === 'single') {
            html += `<div class="wizard-options">`;
            block.options.forEach(option => {
                const isSelected = value === option;
                html += `
                    <div class="wizard-option ${isSelected ? 'selected' : ''}" data-option="${option}">
                        ${option}
                        ${isSelected ? '<span class="wizard-check">✓</span>' : ''}
                    </div>
                `;
            });
            html += `</div>`;
        } else if (block.type === 'multi') {
            const selectedArray = Array.isArray(value) ? value : [];
            html += `<div class="wizard-options">`;
            block.options.forEach(option => {
                const isSelected = selectedArray.includes(option);
                html += `
                    <div class="wizard-option ${isSelected ? 'selected' : ''}" data-option="${option}">
                        ${option}
                        ${isSelected ? '<span class="wizard-check">✓</span>' : ''}
                    </div>
                `;
            });
            html += `</div>`;
        } else if (block.type === 'text') {
            html += `<input type="text" data-key="${block.key}" value="${value}" placeholder="${block.placeholder || ''}">`;
        } else if (block.type === 'textarea') {
            html += `<textarea data-key="${block.key}" placeholder="${block.placeholder || ''}">${value}</textarea>`;
        } else if (block.type === 'date') {
            html += `<input type="date" data-key="${block.key}" value="${value}">`;
        }

        html += `</div>`;
        return html;
    }

    // ============================================
    // EVENT LISTENERS
    // ============================================

    function attachEventListeners() {
        // Step 0: Goal selection
        if (state.stepIndex === 0) {
            document.querySelectorAll('[data-goal]').forEach(el => {
                el.addEventListener('click', function() {
                    const goal = this.getAttribute('data-goal');
                    const index = state.goals.indexOf(goal);
                    if (index > -1) {
                        state.goals.splice(index, 1);
                    } else {
                        state.goals.push(goal);
                    }
                    render();
                });
            });
        }
        // Journey blocks: Single option selection
        else if (state.stepIndex > 0 && state.stepIndex <= state.goals.length) {
            const goalIndex = state.stepIndex - 1;
            const journeySlug = GOAL_TO_JOURNEY[state.goals[goalIndex]];

            document.querySelectorAll('.wizard-field').forEach(field => {
                const blockKey = field.getAttribute('data-block-key');
                const options = field.querySelectorAll('[data-option]');

                options.forEach(option => {
                    option.addEventListener('click', function() {
                        const optionValue = this.getAttribute('data-option');
                        const block = JOURNEYS[journeySlug]?.blocks.find(b => b.key === blockKey);

                        if (block?.type === 'single') {
                            state.journeyAnswers[journeySlug][blockKey] = optionValue;
                        } else if (block?.type === 'multi') {
                            const arr = state.journeyAnswers[journeySlug][blockKey] || [];
                            if (!Array.isArray(arr)) state.journeyAnswers[journeySlug][blockKey] = [];
                            const idx = state.journeyAnswers[journeySlug][blockKey].indexOf(optionValue);
                            if (idx > -1) {
                                state.journeyAnswers[journeySlug][blockKey].splice(idx, 1);
                            } else {
                                state.journeyAnswers[journeySlug][blockKey].push(optionValue);
                            }
                        }
                        render();
                    });
                });

                // Text and textarea inputs
                const textInput = field.querySelector('input[data-key], textarea[data-key]');
                if (textInput) {
                    textInput.addEventListener('change', function() {
                        state.journeyAnswers[journeySlug][blockKey] = this.value;
                    });
                }
            });
        }
        // Contact info step
        else if (state.stepIndex === state.goals.length + 1) {
            document.querySelectorAll('#contactFields input, #contactFields select').forEach(el => {
                el.addEventListener('change', function() {
                    const name = this.name;
                    state.contact[name] = this.value;
                });
            });
        }
        // Global fields
        else if (state.stepIndex === state.goals.length + 2) {
            document.querySelectorAll('#globalFields select, #globalFields textarea').forEach(el => {
                el.addEventListener('change', function() {
                    const name = this.name;
                    state.globalAnswers[name] = this.value;
                });
            });
        }

        // Navigation buttons
        document.querySelector('[data-action="back"]')?.addEventListener('click', () => {
            if (state.stepIndex > 0) {
                state.stepIndex--;
                render();
                window.scrollTo(0, 0);
            }
        });

        document.querySelector('[data-action="next"]')?.addEventListener('click', () => {
            const totalSteps = 4 + state.goals.length;
            if (state.stepIndex < totalSteps - 1) {
                // Validation
                if (state.stepIndex === 0 && state.goals.length === 0) {
                    alert('Please select at least one service');
                    return;
                }
                state.stepIndex++;
                render();
                window.scrollTo(0, 0);
            }
        });

        document.querySelector('[data-action="submit"]')?.addEventListener('click', submitForm);
    }

    // ============================================
    // FORM SUBMISSION
    // ============================================

    function submitForm() {
        // Validation
        if (!state.contact.fullName || !state.contact.email || !state.contact.projectName) {
            alert('Please fill in all required fields (Name, Email, Project Name)');
            return;
        }

        const form = new FormData();
        form.append('action', 'submit_expert_request');
        form.append('nonce', window.expertRequestData.nonce);
        form.append('goals', JSON.stringify(state.goals));
        form.append('journeyAnswers', JSON.stringify(state.journeyAnswers));
        form.append('globalAnswers', JSON.stringify(state.globalAnswers));
        form.append('fullName', state.contact.fullName);
        form.append('projectName', state.contact.projectName);
        form.append('company', state.contact.company);
        form.append('country', state.contact.country);
        form.append('email', state.contact.email);
        form.append('phone', state.contact.phone);
        form.append('preferredContact', state.contact.preferredContact);
        form.append('availability', state.contact.availability);
        form.append('notes', state.contact.notes);

        // Show loading state
        const root = document.getElementById('wizard-root');
        const originalContent = root.innerHTML;
        root.innerHTML = '<div class="wizard-card"><p class="wizard-loading">Submitting your request...</p></div>';

        fetch(window.expertRequestData.ajaxurl, {
            method: 'POST',
            body: form,
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Success! Redirect
                window.location.href = data.data.redirect;
            } else {
                alert('Error: ' + (data.data?.message || 'Unknown error'));
                root.innerHTML = originalContent;
                attachEventListeners();
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error submitting form. Please try again.');
            root.innerHTML = originalContent;
            attachEventListeners();
        });
    }

    // ============================================
    // INITIALIZATION
    // ============================================

    // Start the wizard
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', render);
    } else {
        render();
    }
})();
